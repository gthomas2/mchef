<?php

namespace App\Command;

use App\Service\Configurator;
use App\Service\Main;
use App\Service\ProxyService;
use App\StaticVars;
use App\Traits\ExecTrait;
use App\Traits\SingletonTrait;
use splitbrain\phpcli\Options;
use MChefCLI;

class Up extends AbstractCommand {

    use SingletonTrait;
    use ExecTrait;

    const COMMAND_NAME = 'up';

    final public static function instance(MChefCLI $cli): Up {
        $instance = self::setup_singleton($cli);
        return $instance;
    }

    public function execute(Options $options): void {
        $this->setStaticVarsFromOptions($options);
        $instance = StaticVars::$instance;
        $containerPrefix = $instance->containerPrefix;

        if (empty($containerPrefix)) {
            $this->cli->error('Please provide a container prefix name or set an active instance via the use command.');
            $this->cli->info('Usage: mchef up <container-prefix>');
            $this->cli->info('Example: mchef up ally');
            exit(1);
        }

        // Build expected container names from the prefix
        $moodleContainer = $containerPrefix . '-moodle';
        $dbContainer = $containerPrefix . '-db';

        // Check if containers exist
        $existingContainers = $this->getExistingContainers([$moodleContainer, $dbContainer]);

        if (empty($existingContainers)) {
            $this->showMissingContainersError($containerPrefix);
            exit(1);
        }

        // Check if all expected containers exist
        $missingContainers = [];
        if (!in_array($moodleContainer, $existingContainers)) {
            $missingContainers[] = $moodleContainer;
        }
        if (!in_array($dbContainer, $existingContainers)) {
            $missingContainers[] = $dbContainer;
        }

        if (!empty($missingContainers)) {
            $this->cli->error("Missing containers: " . implode(', ', $missingContainers));
            $this->showMissingContainersError($containerPrefix);
            exit(1);
        }

        // Start the containers
        $this->cli->info("Starting containers for: $containerPrefix");
        $this->startContainers([$moodleContainer, $dbContainer]);

        // Handle proxy mode
        $proxyService = ProxyService::instance($this->cli);
        $proxyService->ensureProxyRunning();

        $this->cli->success('Containers started successfully!');
    }

    private function showMissingContainersError(string $containerPrefix): void {
        $this->cli->error("Missing containers for prefix: $containerPrefix");

        // Try to find the project directory from registry for helpful error message
        $instances = Configurator::instance($this->cli)->getInstanceRegistry();
        $foundInstance = null;

        foreach ($instances as $instance) {
            if ($instance->containerPrefix === $containerPrefix) {
                $foundInstance = $instance;
                break;
            }
        }

        if ($foundInstance) {
            $projectDir = dirname($foundInstance->recipePath);
            $recipeFile = basename($foundInstance->recipePath);
            $this->cli->info("You must re-run mchef in the project directory for this recipe:");
            $this->cli->info("  cd $projectDir");
            $this->cli->info("  mchef $recipeFile");
        } else {
            $this->cli->info("You need to reinitialize by running mchef <recipe-file.json> in the project directory.");
        }
    }

    private function getExistingContainers(array $expectedContainers): array {
        $existing = [];

        foreach ($expectedContainers as $containerName) {
            // Check if container exists (running or stopped)
            $cmd = "docker ps -a --filter name=^{$containerName}$ --format \"{{.Names}}\"";
            exec($cmd, $output, $returnVar);

            if ($returnVar === 0 && !empty($output) && in_array($containerName, $output)) {
                $existing[] = $containerName;
            }
        }

        return $existing;
    }

    private function startContainers(array $containers): void {
        foreach ($containers as $container) {
            $this->cli->info("Starting container: $container");

            $cmd = "docker start $container";
            exec($cmd, $output, $returnVar);

            if ($returnVar !== 0) {
                $this->cli->error("Failed to start container: $container");
                $this->cli->error(implode("\n", $output));
            } else {
                $this->cli->success("Started: $container");
            }
        }
    }

    public function register(Options $options): void {
        $options->registerCommand(self::COMMAND_NAME, 'Start existing mchef docker containers by container prefix');
        $options->registerArgument('prefix', 'Container prefix to start (e.g., "ally" for ally-moodle, ally-db)', false, self::COMMAND_NAME);
    }
}
