<?php

namespace App\Command;

use App\Service\Configurator;
use App\Service\Docker;
use App\Service\Main;
use App\Service\RecipeParser;
use App\Traits\ExecTrait;
use App\Traits\SingletonTrait;
use splitbrain\phpcli\Options;
use MChefCLI;

class Halt extends AbstractCommand {

    use SingletonTrait;
    use ExecTrait;

    const COMMAND_NAME = 'halt';

    final public static function instance(MChefCLI $cli): Halt {
        $instance = self::setup_instance($cli);
        return $instance;
    }

    public function execute(Options $options): void {
        $args = $options->getArgs();
        
        // If container prefix is provided as argument
        if (!empty($args)) {
            $containerPrefix = $args[0];
            $this->haltContainersByPrefix($containerPrefix);
            return;
        }

        // Try to find recipe in current directory
        $mainService = Main::instance($this->cli);
        $chefPath = $mainService->getChefPath(false);
        
        if (!$chefPath) {
            $this->cli->error('You are not in a project directory and no container prefix was specified.');
            $this->cli->info('Usage:');
            $this->cli->info('  mchef halt <container-prefix>  - Stop containers by prefix');
            $this->cli->info('  mchef halt                     - Stop containers for current project (run from project directory)');
            $this->cli->info('');
            $this->cli->info('Examples:');
            $this->cli->info('  mchef halt ally               - Stop ally-moodle, ally-db containers');
            $this->cli->info('  cd /path/to/project && mchef halt - Stop containers for the project in current directory');
            exit(1);
        }

        // We're in a project directory, get the recipe and stop containers
        try {
            $recipe = $mainService->getRecipe();
            $this->cli->notice("Stopping containers for project: {$recipe->containerPrefix}");
            $mainService->stopContainers($recipe);
        } catch (\Exception $e) {
            $this->cli->error('Failed to stop containers: ' . $e->getMessage());
            exit(1);
        }
    }

    private function haltContainersByPrefix(string $containerPrefix): void {
        $this->cli->notice("Stopping containers with prefix: $containerPrefix");
        
        // Build expected container names from the prefix
        $moodleContainer = $containerPrefix . '-moodle';
        $dbContainer = $containerPrefix . '-db';
        $behatContainer = $containerPrefix . '-behat';
        
        $dockerService = Docker::instance($this->cli);
        $containers = $dockerService->getDockerContainers(false);
        $stoppedContainers = 0;
        
        $containersToStop = [$moodleContainer, $dbContainer];
        
        foreach ($containers as $container) {
            $name = $container->names;
            
            // Stop if it matches our expected containers or starts with behat prefix
            if (in_array($name, $containersToStop) || strpos($name, $behatContainer) === 0) {
                $this->cli->notice('Stopping container: ' . $name);
                $dockerService->stopDockerContainer($name);
                $stoppedContainers++;
            }
        }

        if ($stoppedContainers > 0) {
            $this->cli->success("Stopped $stoppedContainers container(s) for prefix: $containerPrefix");
        } else {
            $this->cli->info("No running containers found for prefix: $containerPrefix");
            
            // Try to provide helpful information from registry
            $this->showRegistryInfo($containerPrefix);
        }
    }

    private function showRegistryInfo(string $containerPrefix): void {
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
            $this->cli->info("This prefix is registered. To recreate containers:");
            $this->cli->info("  cd $projectDir");
            $this->cli->info("  mchef $recipeFile");
        } else {
            $this->cli->info("No registry entry found for prefix: $containerPrefix");
            $this->cli->info("Use 'mchef list' to see all registered instances.");
        }
    }

    public function register(Options $options): void {
        $options->registerCommand(self::COMMAND_NAME, 'Stop docker containers for a project or by container prefix');
        $options->registerArgument('prefix', 'Container prefix to stop (optional if run from project directory)', false, self::COMMAND_NAME);
    }
}
