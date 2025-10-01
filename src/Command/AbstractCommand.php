<?php

namespace App\Command;

use App\Interfaces\SingletonInterface;
use App\Model\RegistryInstance;
use App\Service\Configurator;
use App\Service\Main;
use App\StaticVars;
use App\Traits\SingletonTrait;
use RuntimeException;
use splitbrain\phpcli\Options;

abstract class AbstractCommand implements SingletonInterface {

    use SingletonTrait;

    // Service dependencies.
    protected Main $mainService;
    protected Configurator $configuratorService;

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

        // If instance name is provided as argument
        if (!empty($args)) {
            $instanceName = $args[0];
        } else {
            $instanceName = $this->mainService->resolveActiveInstanceName();
        }

        if (empty($instanceName)) {
            throw new Exception('Could not resolve instance name. Run from within project directory, or select instance with mchef use');
        }
        $instance = $this->configuratorService->getRegisteredInstance($instanceName);
        if (empty($instance)) {
            throw new Exception ('Invalid instance '.$instanceName);
        }
        return $instance;
    }

    protected function setStaticVarsFromOptions(Options $options): void {
        $instance = $this->getInstanceFromOptions($options);
        StaticVars::$instance = $instance;
        $defaultStr = $instance->isDefault ? 'default ' : '';
        $this->cli->info('-- Using '.$defaultStr.'instance "'.$instance->containerPrefix.'" --');

        $recipe = $this->mainService->getRecipe(StaticVars::$instance->recipePath);
        StaticVars::$recipe = $recipe;
    }

    protected function getOptionalOptValueFromArgv(string $long, string $short): ?string {
        global $argv;

        $isOptionToken = static fn(string $s): bool => str_starts_with($s, '-');

        for ($i = 0, $n = count($argv); $i < $n; $i++) {
            $tok = $argv[$i];

            // --[arg]=value
            $prefix = "--{$long}=";
            if (str_starts_with($tok, $prefix)) {
                $argVal = substr($tok, strlen($prefix));
                if (trim($argVal) === '') {
                    return null;
                }
                return $argVal;
            }

            // --[arg] value
            if ($tok === "--{$long}") {
                if ($i + 1 < $n && !$isOptionToken($argv[$i + 1])) {
                    $argVal = $argv[$i + 1];
                    if (trim($argVal) === '') {
                        return null;
                    }
                    return $argVal;
                }
                return null;
            }

            // -[shortarg]value
            $shortPrefix = "-{$short}";
            if (str_starts_with($tok, $shortPrefix) && strlen($tok) > strlen($shortPrefix)) {
                $argVal = substr($tok, strlen($shortPrefix));
                if (trim($argVal) === '') {
                    return null;
                }
                return $argVal;
            }

            // -[shortarg] value
            if ($tok === "-{$short}") {
                if ($i + 1 < $n && !$isOptionToken($argv[$i + 1])) {
                    $argVal = $argv[$i + 1];
                    if (trim($argVal) === '') {
                        return null;
                    }
                    return $argVal;
                }
                return null;
            }
        }

        return null;
    }
}
