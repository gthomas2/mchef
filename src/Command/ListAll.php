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
use App\MChefCLI;

class ListAll extends AbstractCommand {
    // Allow dependency injection for easier testing
    protected $configurator;
    protected $main;
    protected $docker;
    protected $recipeParser;

    use SingletonTrait;
    use ExecTrait;

    const COMMAND_NAME = 'list';

    final public static function instance(MChefCLI $cli): ListAll {
        $instance = self::setup_singleton($cli);
        return $instance;
    }

    public function execute(Options $options): void {
        $configurator = $this->configurator ?? Configurator::instance($this->cli);
        $main = $this->main ?? Main::instance($this->cli);
        $docker = $this->docker ?? Docker::instance($this->cli);
        $recipeParser = $this->recipeParser ?? RecipeParser::instance();
        $instances = $configurator->getInstanceRegistry();
        $config = $configurator->getMainConfig();
        $selectedInstance = $config->instance ?? null;
        foreach ($instances as $instance) {
            if (!file_exists($instance->recipePath)) {
                $this->cli->warning('⚠️ Recipe missing '.$instance->recipePath);
            }

            $recipe = $recipeParser->parse($instance->recipePath);
            $moodleContainerName = $main->getDockerMoodleContainerName(null, $recipe);

            try {
                $running = $docker->checkContainerRunning($moodleContainerName);
            } catch (\Exception $e) {
                $running = false; // Assume not running.
            }

            $symbol = $running ? '✅' : '⏸️ '; // Not sure why but we need an extra space after pause!
            $selectedMark = ($selectedInstance && $instance->containerPrefix === $selectedInstance)
                ? " \033[32m*SELECTED*\033[0m" : '';
            echo($symbol.' '.$instance->containerPrefix.' - '.($running ? 'up' : 'inactive').$selectedMark."\n");
        }
    }

    public function register(Options $options): void {
        $options->registerCommand(self::COMMAND_NAME, 'List all mchef recipes and statuses');
    }
}
