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

    final public static function instance(MChefCLI $cli): PHPUnit {
        $instance = self::setup_instance($cli);
        return $instance;
    }

    public function execute(Options $options): void {
        $recipe = (Main::instance($this->cli))->getRecipe();
        $pluginsService = Plugins::instance($this->cli);
        if (!$recipe->includePhpUnit) {
            throw new Exception('This recipe does not have includePhpUnit set to true, OR you need to run mchef.php [recipefile] again.');
        }
        $docker = Docker::instance();

        $this->cli->notice('Initializing PHPUnit');
        $moodleContainer = $recipe->containerPrefix.'-moodle';
        $cmd = 'docker exec -it '.$moodleContainer.' php /var/www/html/moodle/admin/tool/phpunit/cli/init.php';
        $this->execStream($cmd, 'Failed to initialize phpunit');
        $runCode = '/var/www/html/moodle/vendor/bin/phpunit';

        $plugins = $pluginsService->getPluginsCsvFromOptions($options);
        $pluginsService->validatePluginComponentNames($plugins);
        $groups = $options->getOpt('group');
        $runMsg = 'Executing phpunit tests';
        if (!empty($plugins)) {
            $runMsg .= " for plugins ".implode(',', array_keys($plugins));
            if (!empty($groups)) {
                $runMsg .= " and groups ".$groups;
            }
            $pluginTestPaths = [];
            foreach ($plugins as $componentName) {
                $plugin = $pluginsService->getPluginByComponentName($componentName);
                $pluginTestPaths[] = $plugin->path.'/tests';
            }
        } else if (!empty($groups)) {
            $runMsg .= " for groups ".$groups;
        }
        if (!empty($groups)) {
            $runCode .= ' --group='.$groups;
        }
        if (!empty($pluginTestPaths)) {
            $runCode .= ' '.implode(' ', $pluginTestPaths);
        }
        $this->cli->notice($runMsg);
        $cmd = 'docker exec -it '.$moodleContainer.' '.$runCode;
        die ($cmd);
        //$this->execPassthru($cmd, 'Tests failed');
    }

    public function register(Options $options): void {
        $options->registerCommand('phpunit', 'Allows phpunit tests to be run against plugins defined in the recipe file.');
        $options->registerOption('plugins',
            'Plugin frankenstyle names to run phpunit tests against. Leave this argument empty for all plugins. For multiple plugins, separate using a comma.',
            'p', false, 'phpunit');
        $options->registerOption('groups', 'Limit your tests to ones tagged with specific groups - e.g @group local_someplugin',
            null, false, 'phpunit');
    }
}