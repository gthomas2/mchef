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

class PurgeCaches extends AbstractCommand {

    use SingletonTrait;
    use ExecTrait;

    const COMMAND_NAME = 'purgecaches';

    final public static function instance(MChefCLI $cli): PurgeCaches {
        $instance = self::setup_singleton($cli);
        return $instance;
    }

    public function execute(Options $options): void {
        $this->setStaticVarsFromOptions($options);
        $instanceName = StaticVars::$instance->containerPrefix;
        $mainService = Main::instance($this->cli);

        $containerName = $mainService->getDockerMoodleContainerName($instanceName);
        $cmd = 'docker exec -it '.$containerName.' php /var/www/html/moodle/admin/cli/purge_caches.php';
        $this->exec($cmd, 'Failed to purge caches for '.$instanceName);
        $this->cli->success('Caches successfully purged for '.$instanceName);
    }

    public function register(Options $options): void {
        $options->registerCommand(self::COMMAND_NAME, 'Purge moodle caches');
        $options->registerArgument('prefix', 'Mchef instance name to purge caches (optional if run from project directory)', false, self::COMMAND_NAME);
    }
}
