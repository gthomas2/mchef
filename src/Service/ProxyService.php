<?php

namespace App\Service;

use App\Helpers\OS;
use App\Model\GlobalConfig;
use App\Model\RegistryInstance;
use App\Model\Recipe;
use App\Traits\ExecTrait;
use splitbrain\phpcli\CLI;

class ProxyService extends AbstractService {

    use ExecTrait;

    const PROXY_CONTAINER_NAME = 'mchef-proxy';
    const PROXY_PORT = 80;

    protected function __construct() {
        // Initialize
    }

    final public static function instance(?CLI $cli = null): ProxyService {
        return self::setup_instance($cli);
    }

    /**
     * Check if proxy mode is enabled in global config
     */
    public function isProxyModeEnabled(): bool {
        $globalConfig = Configurator::instance($this->cli)->getMainConfig();
        return $globalConfig->useProxy ?? false;
    }

    /**
     * Check if proxy container is running
     */
    public function isProxyContainerRunning(): bool {
        $cmd = "docker ps --filter name=" . self::PROXY_CONTAINER_NAME . " --format \"{{.Names}}\"";
        exec($cmd, $output, $returnVar);

        return $returnVar === 0 && !empty($output) && in_array(self::PROXY_CONTAINER_NAME, $output);
    }

    /**
     * Check if proxy container exists (running or stopped)
     */
    public function doesProxyContainerExist(): bool {
        $cmd = "docker ps -a --filter name=" . self::PROXY_CONTAINER_NAME . " --format \"{{.Names}}\"";
        exec($cmd, $output, $returnVar);

        return $returnVar === 0 && !empty($output) && in_array(self::PROXY_CONTAINER_NAME, $output);
    }

    /**
     * Start the proxy container
     */
    public function startProxyContainer(): void {
        if ($this->isProxyContainerRunning()) {
            $this->cli->info('Proxy container is already running');
            return;
        }

        if ($this->doesProxyContainerExist()) {
            $this->cli->info('Starting existing proxy container');
            $cmd = "docker start " . self::PROXY_CONTAINER_NAME;
        } else {
            $this->cli->info('Creating and starting proxy container');
            $configPath = $this->getProxyConfigPath();
            $cmd = "docker run -d --name " . self::PROXY_CONTAINER_NAME .
                   " -p " . self::PROXY_PORT . ":" . self::PROXY_PORT .
                   " -v \"$configPath:/etc/nginx/conf.d/default.conf:ro\" " .
                   " --network mc-network nginx:alpine";
        }

        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->cli->error("Failed to start proxy container");
            $this->cli->error(implode("\n", $output));
        } else {
            $this->cli->success("Proxy container started successfully");
        }
    }

    /**
     * Restart the proxy container
     */
    public function restartProxyContainer(): void {
        if (!$this->doesProxyContainerExist()) {
            $this->startProxyContainer();
            return;
        }

        $this->cli->info('Restarting proxy server');

        $cmd = "docker restart " . self::PROXY_CONTAINER_NAME;
        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->cli->error("Failed to restart proxy container");
            $this->cli->error(implode("\n", $output));
        } else {
            $this->cli->success('Proxy server restarted');
        }
    }

    /**
     * Get the path to the proxy configuration file
     */
    public function getProxyConfigPath(): string {
        $configDir = Configurator::instance($this->cli)->configDir();
        return OS::path($configDir . '/proxy.conf');
    }

    /**
     * Generate and write the nginx proxy configuration
     */
    public function generateProxyConfig(): void {
        $instances = Configurator::instance($this->cli)->getInstanceRegistry();
        $config = $this->buildNginxConfig($instances);

        $configPath = $this->getProxyConfigPath();
        file_put_contents($configPath, $config);

        $this->cli->info("Generated proxy configuration at: $configPath");
    }

    /**
     * Build nginx configuration content
     */
    private function buildNginxConfig(array $instances): string {
        $config = "# Auto-generated nginx proxy configuration for mchef\n\n";

        foreach ($instances as $instance) {
            if ($instance->proxyModePort === null) {
                continue; // Skip non-proxy instances
            }

            try {
                $recipe = Recipe::fromJSONFile($instance->recipePath);
                if (empty($recipe->host)) {
                    continue; // Skip instances without host configuration
                }

                $config .= "upstream {$instance->containerPrefix}_backend {\n";
                $config .= "    server host.docker.internal:{$instance->proxyModePort};\n";
                $config .= "}\n\n";

                $recipeParser = RecipeParser::instance();
                $behatHost = $recipeParser->getBehatHost($recipe);
                $serverName = $recipe->host;
                if ($behatHost) {
                    $serverName .= ' '.$behatHost;
                }

                $config .= "server {\n";
                $config .= "    listen 80;\n";
                $config .= "    server_name {$serverName};\n\n";
                $config .= "    location / {\n";
                $config .= "        proxy_pass http://{$instance->containerPrefix}_backend;\n";
                $config .= "        proxy_set_header Host \$host;\n";
                $config .= "        proxy_set_header X-Real-IP \$remote_addr;\n";
                $config .= "        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;\n";
                $config .= "        proxy_set_header X-Forwarded-Proto \$scheme;\n";
                $config .= "    }\n";
                $config .= "}\n\n";
            } catch (\Exception $e) {
                $this->cli->warning("Could not parse recipe for {$instance->containerPrefix}: " . $e->getMessage());
                continue;
            }
        }

        // Add default server block to handle requests to unknown hosts
        $config .= "server {\n";
        $config .= "    listen 80 default_server;\n";
        $config .= "    server_name _;\n";
        $config .= "    return 444;\n";
        $config .= "}\n";

        return $config;
    }

    /**
     * Update proxy configuration and restart container
     */
    public function updateProxyConfiguration(): void {
        if (!$this->isProxyModeEnabled()) {
            return;
        }

        $this->generateProxyConfig();

        if ($this->doesProxyContainerExist()) {
            $this->restartProxyContainer();
        } else {
            $this->startProxyContainer();
        }
    }

    /**
     * Ensure proxy is running if in proxy mode
     */
    public function ensureProxyRunning(): void {
        if (!$this->isProxyModeEnabled()) {
            return;
        }

        if (!$this->isProxyContainerRunning()) {
            $this->cli->info('Proxy mode is enabled but proxy container is not running');
            $this->updateProxyConfiguration();
        }
    }
}
