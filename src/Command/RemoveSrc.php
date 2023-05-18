<?php

namespace App\Command;

use App\Model\Recipe;
use App\Service\Docker;
use App\Service\File;
use App\Service\Main;
use App\Service\Plugins;
use App\Service\Project;
use App\Traits\ExecTrait;
use App\Traits\SingletonTrait;
use splitbrain\phpcli\Options;
use MChefCLI;

class RemoveSrc extends AbstractCommand {

    use SingletonTrait;
    use ExecTrait;

    const COMMAND_NAME = 'removesrc';

    protected Recipe $recipe;

    final public static function instance(MChefCLI $cli): RemoveSrc {
        $instance = self::setup_instance($cli);
        return $instance;
    }

    public function execute(Options $options): void {
        Project::instance($this->cli)->purgeProjectFolderOfNonPluginCode();
    }

    public function register(Options $options): void {
        $options->registerCommand(self::COMMAND_NAME, 'Remove Moodle source from project folder');
    }
}