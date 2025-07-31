<?php

namespace App\Command;

use App\Model\Recipe;
use App\Service\Configurator;
use App\Service\Docker;
use App\Service\File;
use App\Service\Main;
use App\Service\Plugins;
use App\Service\Project;
use App\Traits\ExecTrait;
use App\Traits\SingletonTrait;
use splitbrain\phpcli\Options;
use MChefCLI;

class UseCmd extends AbstractCommand {

    use SingletonTrait;
    use ExecTrait;

    const COMMAND_NAME = 'use';

    final public static function instance(MChefCLI $cli): UseCmd {
        $instance = self::setup_singleton($cli);
        return $instance;
    }

    private function setInstance(string $instance) {
        Configurator::instance($this->cli)->setMainConfigField('instance', $instance);
        $this->cli->success('Active instance is now "'.$instance.'"');
    }

    private function validateInstance(string $instanceName) {
        $instances = Configurator::instance($this->cli)->getInstanceRegistry();
        $names = array_map(function ($inst) {
            return $inst->containerPrefix;
        }, $instances);
        return in_array($instanceName, $names);
    }

    public function execute(Options $options): void {
        $arg = $options->getArgs()[0] ?? '';
        if (!$arg) {
            $this->cli->error('You must specify a valid instance to use');
            return;
        }
        $valid = $this->validateInstance($arg);
        if (!$valid) {
            $this->cli->error('The instance '.$arg.' is invalid');
            return;
        }
        $this->setInstance($arg);
    }

    public function register(Options $options): void {
        $options->registerCommand(self::COMMAND_NAME, 'Select the mchef instance you would like to use - name must be registered. Call mchef list to see list of registered instances.');
    }
}
