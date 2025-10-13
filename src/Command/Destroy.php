<?php

namespace App\Command;

use App\Service\Docker;
use App\Service\File;
use App\StaticVars;
use App\Traits\ExecTrait;
use App\Traits\SingletonTrait;
use Exception;
use splitbrain\phpcli\Options;

class Destroy extends AbstractCommand {

    use SingletonTrait;
    use ExecTrait;

    // Service dependencies.
    private Docker $dockerService;
    private File $fileService;

    // Constants.
    const COMMAND_NAME = 'destroy';

    final public static function instance(): Destroy {
        return self::setup_singleton();
    }

    public function execute(Options $options): void {
        $args = $options->getArgs();
        
        if (empty($args) || empty($args[0])) {
            $this->cli->error('Instance name is required.');
            $this->cli->info('Usage: mchef destroy <instance-name> [--dry-run]');
            $this->cli->info('Example: mchef destroy my-project');
            $this->cli->info('Example: mchef destroy my-project --dry-run');
            exit(1);
        }

        // Use the standard pattern like other commands, but provide helpful error messages
        try {
            $this->setStaticVarsFromOptions($options);
            $instance = StaticVars::$instance;
            $instanceName = $instance->containerPrefix;
        } catch (Exception $e) {
            // Check if it's an unregistered instance and provide helpful error message
            $instanceName = $args[0];
            $registeredInstance = $this->configuratorService->getRegisteredInstance($instanceName);
            
            if (!$registeredInstance) {
                $this->cli->error("Instance '$instanceName' is not registered.");
                $this->cli->info("Available instances:");
                $instances = $this->configuratorService->getInstanceRegistry();
                if (empty($instances)) {
                    $this->cli->info("  No instances found.");
                } else {
                    foreach ($instances as $instance) {
                        $this->cli->info("  - {$instance->containerPrefix}");
                    }
                }
            } else {
                // Re-throw if it's a different error (like validation)
                $this->cli->error($e->getMessage());
            }
            exit(1);
        }

        // Check if this is a dry run
        $isDryRun = (bool) $options->getOpt('dry-run');

        // Show what will be destroyed
        if ($isDryRun) {
            $this->cli->info("DRY RUN: The following would be destroyed for instance '$instanceName':");
        } else {
            $this->cli->warning("The following will be destroyed for instance '$instanceName':");
        }
        $this->cli->info("  - Container: {$instanceName}-moodle");
        $this->cli->info("  - Container: {$instanceName}-db");
        $volumes = $this->dockerService->getInstanceVolumes($instanceName);
        if (!empty($volumes)) {
            foreach ($volumes as $volume) {
                $this->cli->info("  - Volume: $volume");
            }
        } else {
            $this->cli->info("  - No associated volumes found");
        }
        $this->cli->info("  - Instance registration");

        // Check for mchef directory that would be deleted
        $mchefPath = $this->mainService->getChefPath();
        if (!empty($mchefPath) && is_dir($mchefPath)) {
            if (basename($mchefPath) === '.mchef') {
                $this->cli->info("  - Directory: $mchefPath");
            }
        }

        // If dry run, exit early without prompting or performing actions
        if ($isDryRun) {
            $this->cli->success("DRY RUN: No actual changes were made.");
            return;
        }

        // Safety prompt - require typing "yes" exactly
        $response = $this->cli->promptInput(
            "All associated containers / data will be destroyed. Type 'yes' to confirm: "
        );

        if ($response !== 'yes') {
            $this->cli->info("Destruction cancelled.");
            return;
        }

        $this->cli->notice("Destroying instance '$instanceName'...");

        $mchefPath = $this->mainService->getChefPath();
        if (empty($mchefPath) || !is_dir($mchefPath)) {
            $this->cli->warning("Could not determine mchef path for project.");
        } else {
            if (basename($mchefPath) !== '.mchef') {
                $this->cli->warning("Invalid mchef path $mchefPath");
            } else {
                // Wipe the .mchef directory.
                $this->cli->info("Wiping mchef path: " . ($mchefPath ?: 'unknown'));
                $this->fileService->deleteDir($mchefPath);
            }
        }        

        // Destroy containers and volumes
        $this->destroyContainers($instanceName);
        $this->destroyVolumes($instanceName);

        // Deregister the instance
        $this->deregisterInstance($instanceName);

        $this->cli->success("Instance '$instanceName' has been completely destroyed.");
    }

    private function destroyContainers(string $instanceName): void {
        $containers = [
            "{$instanceName}-moodle",
            "{$instanceName}-db"
        ];

        foreach ($containers as $containerName) {
            $this->cli->info("Checking container: $containerName");
            
            // Safely escape container name for shell usage
            $escapedContainerName = escapeshellarg($containerName);
            
            // Check if container exists
            $cmd = "docker ps -a --filter name=^{$containerName}$ --format \"{{.Names}}\"";
            exec($cmd, $output, $returnVar);

            if ($returnVar === 0 && !empty($output) && in_array($containerName, $output)) {
                $this->cli->info("Stopping and removing container: $containerName");
                
                // Stop the container if running
                $stopCmd = "docker stop $escapedContainerName 2>/dev/null";
                exec($stopCmd);
                
                // Remove the container
                $removeCmd = "docker rm $escapedContainerName";
                exec($removeCmd, $removeOutput, $removeReturnVar);
                
                if ($removeReturnVar === 0) {
                    $this->cli->success("Removed container: $containerName");
                } else {
                    $this->cli->error("Failed to remove container: $containerName");
                    $this->cli->error(implode("\n", $removeOutput));
                }
            } else {
                $this->cli->info("Container not found: $containerName");
            }
        }
    }

    private function destroyVolumes(string $instanceName): void {
        $this->cli->info("Removing volumes for instance: $instanceName");
        
        // Use the Docker service to get volumes attached to the instance containers
        $volumes = $this->dockerService->getInstanceVolumes($instanceName);
        
        if (empty($volumes)) {
            $this->cli->info("No volumes found for instance: $instanceName");
            return;
        }

        foreach ($volumes as $volumeName) {
            $this->cli->info("Removing volume: $volumeName");
            
            if ($this->dockerService->removeVolume($volumeName)) {
                $this->cli->success("Removed volume: $volumeName");
            } else {
                $this->cli->warning("Could not remove volume: $volumeName (may not exist or be in use)");
            }
        }
    }

    private function deregisterInstance(string $instanceName): void {
        $this->cli->info("Deregistering instance: $instanceName");
        
        // If the destroyed instance was the active instance, clear it
        $currentActiveInstance = $this->configuratorService->getMainConfig()->instance;
        if ($currentActiveInstance === $instanceName) {
            $this->configuratorService->setMainConfigField('instance', null);
            $this->cli->info("Cleared active instance (was: $instanceName)");
        }

        // Remove instance from registry using the configurator service
        $success = $this->configuratorService->deregisterInstance($instanceName);
        
        if ($success) {
            $this->cli->success("Instance '$instanceName' deregistered successfully.");
        } else {
            $this->cli->warning("Instance '$instanceName' was not found in registry (may have already been removed).");
        }
    }

    protected function register(Options $options): void {
        $options->registerCommand(self::COMMAND_NAME, 'Completely destroy an mchef instance (containers, volumes, and registration)');
        $options->registerArgument('instance', 'Instance name to destroy (e.g., "my-project")', true, self::COMMAND_NAME);
        $options->registerOption('dry-run', 'Show what would be destroyed without actually performing any deletions', null, false, self::COMMAND_NAME);
    }
}
