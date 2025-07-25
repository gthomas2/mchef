<?php

namespace App\Service;
use App\Helpers\OS;
use App\Model\GlobalConfig;
use App\Model\RegistryInstance;
use splitbrain\phpcli\CLI;

class Configurator extends AbstractService {

    protected function __construct() {
        $this->initializeConfig();
    }

    final public static function instance(?CLI $cli = null): Configurator {
        return self::setup_instance($cli);
    }

    private function initializeConfig(): void {
        $this->establishConfigDir();
    }

    public function configDir(): string {
        // Note can't realPath both because mchef dir might not exist.
        return OS::realPath('~').OS::path('/.config/mchef');
    }

    private function mainConfigPath(): string {
        return OS::path($this->configDir().'/main.json');
    }

    private function createDirIfNotExists(string $dir, string $onErrorMsg): void {
        try {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        } catch (\Exception $e) {
            throw new \Error($onErrorMsg.': '.$dir, 0, $e);
        }
    }

    private function establishConfigDir(): void {
        $this->createDirIfNotExists($this->configDir(), 'Failed to create config dir');
    }

    private function getRegistryFilePath(): string {
        return OS::path($this->configDir().'/registry.txt');
    }

    private function serialzeRegistryInstance(RegistryInstance $instance) {
        $port = $instance->proxyModePort ?? '';
        return "$instance->uuid|$instance->recipePath|$instance->containerPrefix|$port";
    }

    private function deserializeRegistryInstance(string $instanceRow): ?RegistryInstance {
        $tmparr = explode("|", $instanceRow);
        if (count($tmparr) < 3 || count($tmparr) > 4) {
            // Support both old format (3 elements) and new format (4 elements)
            $this->cli->warning('Invalid instance in registry'. $instanceRow);
            return null;
        }
        
        $proxyModePort = null;
        if (count($tmparr) === 4 && !empty($tmparr[3])) {
            $proxyModePort = (int)$tmparr[3];
        }
        
        return new RegistryInstance($tmparr[0], $tmparr[1], $tmparr[2], $proxyModePort);
    }

    /**
     * @return RegistryInstance[] - hashed by uuid
     */
    public function getInstanceRegistry(): array {
        $path = $this->getRegistryFilePath();
        if (!file_exists($path)) {
            touch($path);
        }
        $instances = [];
        $contents = file_get_contents($path);
        if (!empty(trim($contents))) {
            $rows = explode("\n", $contents);
            foreach ($rows as $row) {
                $instance = $this->deserializeRegistryInstance($row);
                if (!$instance) {
                    continue;
                }
                $instances[$instance->uuid] = $instance;
            }
        }
        return $instances;
    }

    /**
     * @param RegistryInstance[] $instances
     * @return void
     */
    private function writeInstanceRegistry(array $instances) {
        $rows = [];
        foreach ($instances as $instance) {
            $rows[]= $this->serialzeRegistryInstance($instance);
        }
        $content = implode("\n", $rows);
        $path = $this->getRegistryFilePath();
        file_put_contents($path, $content);
    }

    private function upsertRegistryInstance(string $uuid, string $instanceRecipePath, string $containerPrefix) {
        $path = OS::realPath($instanceRecipePath);
        $instances = $this->getInstanceRegistry();
        $globalConfig = $this->getMainConfig();
        
        if (empty($instances[$uuid])) {
            // Check that the recipe path is not registered under another uuid.
            $possibleDuplicates = count(array_filter($instances, fn($inst) => $inst->recipePath === $path || $inst->containerPrefix === $containerPrefix));
            if ($possibleDuplicates > 0) {
                $this->cli->warning("Instance for $containerPrefix is already registered with different uuid(s).");
                $proceed = $this->cli->promptYesNo('Deduplicate existing registered instances?');
                if (!$proceed) {
                    $this->cli->warning("Cannot proceed unless registry is de-duplicated for $containerPrefix");
                    die;
                }
                $instances = array_filter($instances, fn($inst) => $inst->containerPrefix !== $containerPrefix);
            }
        }

        // Allocate proxy mode port if needed
        $proxyModePort = null;
        if ($globalConfig->useProxy) {
            $proxyModePort = $this->allocateProxyPort($instances);
        }

        $instances[$uuid] = new RegistryInstance($uuid, $instanceRecipePath, $containerPrefix, $proxyModePort);

        $this->writeInstanceRegistry($instances);
    }

    public function registerInstance(string $instanceRecipePath, ?string $uuid, string $containerPrefix): string {
        $uuid = $uuid ?? uniqid();
        $this->upsertRegistryInstance($uuid, $instanceRecipePath, $containerPrefix);
        // We need to now put the uuid into the .mchef folder corresponding to the recipe.
        $mchefPath = dirname($instanceRecipePath).'/.mchef';
        file_put_contents($mchefPath.'/registry_uuid.txt', $uuid);
        return $uuid;
    }

    public function getMainConfig() {
        $configPath = $this->mainConfigPath();
        if (!file_exists($configPath)) {
            return new GlobalConfig();
        }
        return GlobalConfig::fromJSONFile($configPath);
    }

    public function writeMainConfig(GlobalConfig $config) {
        $config->toJSONFile($this->mainConfigPath());
    }

    public function setMainConfigField(string $field, $value) {
        $mainConfig = $this->getMainConfig();
        $mainConfig->$field = $value;
        $this->writeMainConfig($mainConfig);
    }

    /**
     * Allocate the next available proxy port starting from 8100
     * @param RegistryInstance[] $instances
     * @return int
     */
    private function allocateProxyPort(array $instances): int {
        $startPort = 8100;
        $usedPorts = [];
        
        // Collect all currently used proxy ports
        foreach ($instances as $instance) {
            if ($instance->proxyModePort !== null) {
                $usedPorts[] = $instance->proxyModePort;
            }
        }
        
        // Find the next available port
        $port = $startPort;
        while (in_array($port, $usedPorts)) {
            $port++;
        }
        
        return $port;
    }
}
