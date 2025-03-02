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

class Bash extends AbstractCommand {

    use SingletonTrait;
    use ExecTrait;

    const COMMAND_NAME = 'bash';

    final public static function instance(MChefCLI $cli): Bash {
        $instance = self::setup_instance($cli);
        return $instance;
    }

    public function execute(Options $options): void {
        $mainService = Main::instance($this->cli);
        $recipe = $mainService->getRecipe();
        $containerName = $recipe->containerPrefix.'-moodle';
        $cmd = 'docker exec -it '.$containerName.' bash';
        $this->execPassthru($cmd);
    }

    public function register(Options $options): void {
        $options->registerCommand(self::COMMAND_NAME, 'Allows behat tests to be run against plugins defined in the recipe file.');
        $options->registerArgument('feature', 'Specific feature file to run.', false, self::COMMAND_NAME);
        $options->registerOption('plugins',
            'Plugin frankenstyle names to run behat tests against. Omit this argument for all plugins. For multiple plugins, separate using a comma.',
            'p', 'plugins', self::COMMAND_NAME);
        $options->registerOption('tags', 'Limit your tests to features and steps containing specific tags - e.g @javascript',
            't', 'tags', self::COMMAND_NAME);
        $options->registerOption('verbose', 'Output more information', 'v', false, self::COMMAND_NAME);
        $options->registerOption('profile', 'Use a specific profile', null, 'profile', self::COMMAND_NAME);
    }
}
