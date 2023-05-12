<?php

namespace App\Command;

use App\Exceptions\ExecFailed;
use App\Model\Plugin;
use App\Service\Docker;
use App\Service\Main;
use App\Service\Plugins;
use App\Traits\ExecTrait;
use App\Traits\SingletonTrait;
use splitbrain\phpcli\Exception;
use splitbrain\phpcli\Options;
use MChefCLI;

class Behat extends AbstractCommand {

    use SingletonTrait;
    use ExecTrait;

    protected string $browser = 'chrome'; // Not configurable for now.

    final public static function instance(MChefCLI $cli): Behat {
        $instance = self::setup_instance($cli);
        return $instance;
    }

    /**
     * @param Options $options
     * @return Plugin[]
     */
    private function getPluginsFromOptions(Options $options): array {
        $recipe = (Main::instance($this->cli))->getRecipe();
        $pluginInfo = (Plugins::instance($this->cli))->getPluginsInfoFromRecipe($recipe);
        if ($args = $options->getArgs()) {
            $pluginsCsv = $args[0];
            $pluginComponentNames = array_map(trim, explode(',', $pluginsCsv));
            $pluginService = $this->cli->main->getPluginService();
            $pluginService->validatePluginComponentNames($pluginComponentNames, $pluginInfo);
            return $pluginService->getPluginsByComponentNames($pluginComponentNames, $pluginInfo);
        }
        return $pluginInfo->plugins;
    }

    private function getBehatRunCodeFromInitOutput(string $initOutput): string {
        // Get match on success line.
        $pattern = '/Acceptance tests environment enabled on (.+), to run the tests use:/';
        $matched = preg_match($pattern, $initOutput, $matches);
        if (!$matched) {
            throw new Exception('Behat initialization seems to have failed: '.$initOutput);
        }
        $fullMatch = trim($matches[0]);

        // Explode init output and try to find success line in it.
        $lines = array_map('trim', explode("\n", $initOutput));
        $pos = array_search($fullMatch, $lines);

        if (stripos($lines[$pos + 1], 'vendor/bin/behat') !== 0) {
            throw new Exception('Behat initialization seems to have failed: '.$initOutput);
        }

        return $lines[$pos + 1];
    }

    public function execute(Options $options): void {
        $recipe = (Main::instance($this->cli))->getRecipe();
        if (!$recipe->includeBehat) {
            throw new Exception('This recipe does not have includeBehat set to true, OR you need to run mchef.php [recipefile] again.');
        }
        $docker = Docker::instance();
        $dockerPs = $docker->getDockerPs();
        $alreadyRunning = false;
        $containerName = $recipe->containerPrefix.'-behat-'.$this->browser;
        foreach ($dockerPs as $container) {
            if ($container->names === $containerName) {
                // Already running docker container for behat chrome.
                $this->cli->info('Skipping starting behat container for '
                    .$this->browser.' as it is already running - container id = '
                    .$container->containerId);
                $alreadyRunning = true;
                break;
            }
        }

        $containerAlreadyExists = false;
        $dockerContainers = $docker->getDockerContainers();
        foreach ($dockerContainers as $container) {
            if ($container->names === $containerName) {
                $containerAlreadyExists = true;
                break;
            }
        }

        if (!$alreadyRunning) {
            if ($containerAlreadyExists) {
                $this->cli->info('Starting existing docker container '.$containerName);
                $cmd = "docker start $containerName";
            } else {
                $this->cli->info('Creating and starting docker container '.$containerName);
                $networkName = $recipe->containerPrefix.'-network';
                $cmd =
                    "docker run --name $containerName --network=$networkName -d -p 4444:4444 -p 7900:7900 --shm-size=\"2g\" selenium/standalone-$this->browser:latest";
            }
            try {
                $this->exec($cmd);
            } catch (ExecFailed $e) {
                throw new Exception('Failed to start docker chrome');
            }
        }

        $this->cli->notice('Initializing behat');
        $moodleContainer = $recipe->containerPrefix.'-moodle';
        $cmd = 'docker exec -it '.$moodleContainer.' php /var/www/html/moodle/admin/tool/behat/cli/init.php';
        $output = $this->exec($cmd, 'Failed to initialize behat');
        $behatRunCode = $this->getBehatRunCodeFromInitOutput($output);
        $behatRunCode = str_replace('vendor/bin/behat', '/var/www/html/moodle/vendor/bin/behat', $behatRunCode);

        $plugins = $this->getPluginsFromOptions($options);
        $tags = $options->getOpt('tags');
        $runMsg = 'Executing behat tests';
        if (!empty($plugins)) {
            $runMsg .= " for plugins ".implode(',', array_keys($plugins));
            if (!empty($tags)) {
                $runMsg .= " and tags ".$tags;
            }
            $pluginTags = array_map(function($comp) {return '@'.$comp;}, array_keys($plugins));
            $tags .= implode(',', $pluginTags);
        } else if (!empty($tags)) {
            $runMsg .= " for tags ".$tags;
        }
        $behatRunCode .= ' --profile=headlesschrome';
        if (!empty($tags)) {
            $behatRunCode .= ' --tags='.$tags;
        }
        $this->cli->notice($runMsg);
        $cmd = 'docker exec -it '.$moodleContainer.' '.$behatRunCode;
        $this->execPassthru($cmd, 'Tests failed');
    }

    public function register(Options $options): void {
        $options->registerCommand('behat', 'Allows behat tests to be run against plugins defined in the recipe file.');
        $options->registerArgument('plugins',
            'Plugin frankenstyle names to run behat tests against. Leave this argument empty for all plugins. For multiple plugins, separate using a comma.',
            false, 'behat');
        $options->registerOption('tags', 'Limit your tests to features and steps containing specific tags - e.g @javascript',
            null, false, 'behat');
    }
}