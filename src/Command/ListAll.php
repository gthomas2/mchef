<?php

namespace App\Command;

use App\Model\Recipe;
use App\Service\Configurator;
use App\Service\Docker;
use App\Service\File;
use App\Service\Main;
use App\Service\Plugins;
use App\Service\Project;
use App\Service\RecipeParser;
use App\Traits\ExecTrait;
use App\Traits\SingletonTrait;
use splitbrain\phpcli\Options;
use MChefCLI;

class ListAll extends AbstractCommand {

    use SingletonTrait;
    use ExecTrait;

    const COMMAND_NAME = 'list';

    final public static function instance(MChefCLI $cli): ListAll {
        $instance = self::setup_instance($cli);
        return $instance;
    }

    public function execute(Options $options): void {
        $instances = Configurator::instance($this->cli)->getInstanceRegistry();
        $recipeParser = RecipeParser::instance();
        $main = Main::instance($this->cli);
        $docker = Docker::instance($this->cli);
        $this->cli->info('Listing registered mchef instances...');
        foreach ($instances as $instance) {
            if (!file_exists($instance->recipePath)) {
                $this->cli->warning('⚠️ Recipe missing '.$instance->recipePath);
            }
            $recipe = $recipeParser->parse($instance->recipePath);
            $moodleContainerName = $main->getDockerMoodleContainerName($recipe);
            try {
                $running = $docker->checkContainerRunning($moodleContainerName);
            } catch (\Exception $e) {
                $running = false; // Assume not running.
            }

            $symbol = $running ? '✅' : '⏸️';
            echo($symbol.' '.$moodleContainerName.' - '.($running ? 'up' : 'down'))."\n";

        }
    }

    public function register(Options $options): void {
        $options->registerCommand(self::COMMAND_NAME, 'List all mchef recipes and statuses');
    }
}
