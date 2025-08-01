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

class PurgeCaches extends AbstractCommand {

    use SingletonTrait;
    use ExecTrait;

    const COMMAND_NAME = 'purgecaches';

    final public static function instance(MChefCLI $cli): PurgeCaches {
        $instance = self::setup_instance($cli);
        return $instance;
    }

    public function execute(Options $options): void {
        $mainService = Main::instance($this->cli);
        $instance = $options->getOpt('instance');

        if ($instance) {
            $containerPrefix = $instance;
        } else{
            $recipe = $mainService->getRecipe();
            $containerPrefix = $recipe->containerPrefix;
        }

        $containerName = $containerPrefix . '-moodle';
        $cmd = 'docker exec -it '.$containerName.' php /var/www/html/moodle/admin/cli/purge_caches.php';
        $this->execPassthru($cmd);
    }

    public function register(Options $options): void {
        $options->registerCommand(self::COMMAND_NAME, 'Purge moodle caches');
        $options->registerOption('instance',
            'Mchef instance name (container prefix)',
            'i', 'instance', self::COMMAND_NAME);
    }
}
