<?php

namespace App\Command;

use App\Exceptions\ExecFailed;
use App\Model\Plugin;
use App\Service\Docker;
use App\Service\Main;
use App\Service\Plugins;
use App\StaticVars;
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
        $instance = self::setup_singleton($cli);
        return $instance;
    }

    public function execute(Options $options): void {
        $mainService = Main::instance($this->cli);
        $this->setStaticVarsFromOptions($options);
        $instance = StaticVars::$instance;
        $instanceName = $instance->containerPrefix;
        $containerName = $mainService->getDockerMoodleContainerName($instanceName);
        $cmd = 'docker exec -it '.$containerName.' bash';
        $this->execPassthru($cmd);
    }

    public function register(Options $options): void {
        $options->registerCommand(self::COMMAND_NAME, 'Establish a bash shell on the moodle container');
        $options->registerArgument('instance', 'Instance name for Moodle bash shell (optional if instance selected, or run from project directory).', false, self::COMMAND_NAME);
    }
}
