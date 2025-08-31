<?php

namespace App\Command;

use App\Interfaces\SingletonInterface;
use App\Model\RegistryInstance;
use App\Service\Configurator;
use App\Service\Main;
use App\StaticVars;
use App\Traits\SingletonTrait;
use splitbrain\phpcli\Exception;
use splitbrain\phpcli\Options;

abstract class AbstractCommand implements SingletonInterface {

    use SingletonTrait;

    /**
     * Execute command with options.
     * @param Options $options
     */
    abstract public function execute(Options $options): void;

    /**
     * Register this command and apply help text to options.
     * @param Options $options
     */
    abstract public function register(Options $options): void;

    protected function getInstanceFromOptions(Options $options): RegistryInstance {
        $args = $options->getArgs();
        $mainService = Main::instance($this->cli);
        // If instance name is provided as argument
        if (!empty($args)) {
            $instanceName = $args[0];
        } else {
            $instanceName = $mainService->resolveActiveInstanceName();
        }
        $instance = Configurator::instance()->getRegisteredInstance($instanceName);
        if (!$instance) {
            throw new Exception ('Invalid instance '.$instanceName);
        }
        return $instance;
    }

    protected function setStaticVarsFromOptions(Options $options): void {
        $instance = $this->getInstanceFromOptions($options);
        StaticVars::$instance = $instance;
        $defaultStr = $instance->isDefault ? 'default ' : '';
        $this->cli->info('-- Using '.$defaultStr.'instance "'.$instance->containerPrefix.'" --');

        $mainService = Main::instance($this->cli);
        $recipe = $mainService->getRecipe(StaticVars::$instance->recipePath);
        StaticVars::$recipe = $recipe;
    }
}
