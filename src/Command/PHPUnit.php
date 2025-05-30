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

class PHPUnit extends AbstractCommand {

    use SingletonTrait;
    use ExecTrait;

    const COMMAND_NAME = 'phpunit';

    final public static function instance(MChefCLI $cli): PHPUnit {
        $instance = self::setup_instance($cli);
        return $instance;
    }

    public function execute(Options $options): void {
        $mainService = Main::instance($this->cli);
        $recipe = $mainService->getRecipe();
        $pluginsService = Plugins::instance($this->cli);
        if (!$recipe->includePhpUnit && !$recipe->developer) {
            throw new Exception('This recipe does not have includePhpUnit set to true, OR you need to run mchef.php [recipefile] again.');
        }

        $this->cli->notice('Initializing PHPUnit');
        $moodleContainer = $mainService->getDockerMoodleContainerName();
        $cmd = 'docker exec -it '.$moodleContainer.' php /var/www/html/moodle/admin/tool/phpunit/cli/init.php';
        $this->execStream($cmd, 'Failed to initialize phpunit');
        $runCode = 'bash -c "cd /var/www/html/moodle && vendor/bin/phpunit';

        $groups = $options->getOpt('group');
        $testsuite = $options->getOpt('testsuite');
        $filter = $options->getOpt('filter');

        $runMsg = 'Executing phpunit tests';

        if (!empty($groups)) {
            $runCode .= ' --group='.$groups;
        } else if (empty($testsuite)) {
            $plugins = $pluginsService->getPluginsCsvFromOptions($options);
            $pluginComps = array_map(function($i) {
                return $i->component;
            }, $plugins);
            $pluginsService->validatePluginComponentNames($pluginComps, $pluginsService->loadMchefPluginsInfo());
            if (!empty($plugins)) {
                $runMsg .= " for plugins ".implode(',', array_keys($plugins));
                if (!empty($groups)) {
                    $runMsg .= " and groups $groups";
                }
                $pluginTestPaths = [];
                foreach ($plugins as $plugin) {
                    $pluginTestPaths[] = $plugin->path.'/tests';
                }
            } else if (!empty($groups)) {
                $runMsg .= " for groups $groups";
            }
            if (!empty($pluginTestPaths)) {
                $runCode .= ' '.implode(' ', $pluginTestPaths);
            }
        } else {
            $runCode .= ' /var/www/html/moodle/';
            $runCode .= ' --testsuite='.$testsuite;
            $runMsg .= ' for testsuite '.$testsuite;
        }
        if ($filter) {
            $runCode .= ' --filter='.$filter;
            $runMsg .= ' with filter '.$filter;
        }
        $this->cli->notice($runMsg);
        $cmd = 'docker exec -it '.$moodleContainer.' '.$runCode.'"';
        $this->cli->notice($cmd);

        $this->execPassthru($cmd, 'Tests failed');
    }

    public function register(Options $options): void {
        $options->registerCommand(self::COMMAND_NAME, 'Allows phpunit tests to be run against plugins defined in the recipe file.');
        $options->registerOption('plugins',
            'Plugin frankenstyle names to run phpunit tests against. Leave this argument empty for all plugins. For multiple plugins, separate using a comma.',
            'p', 'plugins', self::COMMAND_NAME);
        $options->registerOption('group', 'Limit your tests to ones tagged with specific groups - e.g @group local_someplugin',
            'g', 'group', self::COMMAND_NAME);
        $options->registerOption('testsuite', 'Limit your tests to specific test suites. Note - this will override all other options',
            's', 'testsuite', self::COMMAND_NAME);
        $options->registerOption('filter', 'Filter which tests to run',
            'f', 'filter', self::COMMAND_NAME);
    }
}
