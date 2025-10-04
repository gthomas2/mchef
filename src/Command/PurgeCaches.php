<?php

namespace App\Command;

use App\Service\Main;
use App\StaticVars;
use App\Traits\ExecTrait;
use App\Traits\SingletonTrait;
use splitbrain\phpcli\Options;

final class PurgeCaches extends AbstractCommand {

    use SingletonTrait;
    use ExecTrait;

    // Constants.
    const COMMAND_NAME = 'purgecaches';

    public static function instance(): PurgeCaches {
        return self::setup_singleton();
    }

    public function execute(Options $options): void {
        $this->setStaticVarsFromOptions($options);
        $instanceName = StaticVars::$instance->containerPrefix;

        $containerName = $this->mainService->getDockerMoodleContainerName($instanceName);
        $cmd = 'docker exec -it '.$containerName.' php /var/www/html/moodle/admin/cli/purge_caches.php';
        $this->exec($cmd, 'Failed to purge caches for '.$instanceName);
        $this->cli->success('Caches successfully purged for '.$instanceName);
    }

   protected function register(Options $options): void {
        $options->registerCommand(self::COMMAND_NAME, 'Purge moodle caches');
        $options->registerArgument('prefix', 'Mchef instance name to purge caches (optional if run from project directory)', false, self::COMMAND_NAME);
    }
}
