<?php

namespace App\Service;
use App\Helpers\OS;
use App\Model\GlobalConfig;
use App\Model\InstanceConfig;
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
        $this->establishInstancesConfigDir();
    }

    public function configDir(): string {
        // Note can't realPath both because mchef dir might not exist.
        return OS::realPath('~').OS::path('/.config/mchef');
    }

    private function mainConfigPath(): string {
        return OS::path(self::configDir().'/main.json');
    }

    public function instancesConfigDir(): string {
        return OS::path($this->configDir().'/instances');
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

    private function establishInstancesConfigDir(): void {
        $this->createDirIfNotExists($this->instancesConfigDir(), 'Failed to create instances config dir');
    }

    private function getRegistryFilePath(): string {
        return OS::path($this->instancesConfigDir().'/registry.txt');
    }

    private function serialzeRegistryInstance(RegistryInstance $instance) {
        return "$instance->uuid|$instance->recipePath";
    }

    private function deserializeRegistryInstance(string $instanceRow): ?RegistryInstance {
        $tmparr = explode("|", $instanceRow);
        if (count($tmparr) !== 2) {
            // Unexpected - should always have 2 elements.
            // first is uuid, second is instance file path.
            $this->cli->warning('Invalid instance in registry'. $instanceRow);
            return null;
        }
        return new RegistryInstance($tmparr[0], $tmparr[1]);
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

    private function upsertRegistryInstance(string $uuid, string $instanceRecipePath) {
        $path = OS::realPath($instanceRecipePath);
        $instances = $this->getInstanceRegistry();
        if (empty($instances[$uuid])) {
            // Check that the recipe path is not registered under another uuid.
            $possibleDuplicates = count(array_filter($instances, fn($inst) => $inst->recipePath === $path));
            if ($possibleDuplicates > 0) {
                throw new \Exception("Instance for $instanceRecipePath is already registered with a different uuid");
            }
        }

        $instances[$uuid] = new RegistryInstance($uuid, $instanceRecipePath);

        $this->writeInstanceRegistry($instances);
    }

    public function registerInstance(string $instanceRecipePath, ?string $uuid): string {
        $uuid = $uuid ?? uniqid();
        $this->upsertRegistryInstance($uuid, $instanceRecipePath);
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
}
