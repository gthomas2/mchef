<?php

namespace App;

use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;
use App\Helpers\OS;
use App\Helpers\SplitbrainWrapper;

class MChefCLI extends CLI {
    static $version = '1.0.0';

    /**
     * @var \App\Service\Main;
     */
    public $main;
    /**
     * @var \App\Service\Dependencies;
     */
    public $depService;
    /**
     * @var \App\Service\Docker;
     */
    public $dockerService;

    /**
     * @var bool $verbose - verbose mode
     */
    public bool $verbose = false;

    public function __construct($autocatch = true) {
        // Suppress splitbrain deprecation warnings during construction
        SplitbrainWrapper::suppressDeprecationWarnings(function() use ($autocatch) {
            parent::__construct($autocatch);
        });
        StaticVars::$cli = $this;
    }

    private function registerCommands(Options $options) {
        $files = scandir(OS::path(__DIR__.'/../src/Command'));

        $files = array_filter($files, function($file) {
            return strpos($file, '.') !== 0 && strpos($file, 'AbstractCommand') !== 0;
        });
        foreach ($files as $file) {
            $class = 'App\\Command\\'.ucfirst(basename($file, '.php'));
            if (class_exists($class)) {
                $class::instance()->register($options);
            }
        }
    }

    protected function setup(Options $options) {
        // Run dependency checks. If one of them fails, program will die()
        $this->depService = \App\Service\Dependencies::instance($this);
        $this->depService->check();

        $options->setHelp('Facilitates the creation of moodle docker instances with custom configurations.');
        $this->registerCommands($options);
        $options->registerArgument('recipe', 'File location of recipe', false);
        $options->registerOption('start', 'Start all containers associated with this recipe', 's');
        $options->registerOption('installexec', 'Install executable version of mchef.php to users bin folder', 'i');
        $options->registerOption('version', 'Print version', 'v');
    }

    private function welcomeLine() {
        $welcomeLine = 'Mchef: '.self::$version.' © Citricity 2024 onwards. www.citricity.com';
        $this->info($welcomeLine);
    }

    protected function main(Options $options) {
        $this->main = \App\Service\Main::instance($this);

        if ($cmd = $options->getCmd()) {
            $this->welcomeLine();
            $class = 'App\\Command\\'.ucfirst($cmd);
            if (!class_exists($class)) {
                $cli = \App\Service\CliService::instance();
                $class = $cli->locateCommandClass($cmd);
                if (!$class || !class_exists($class)) {
                    throw new \splitbrain\phpcli\Exception('Invalid command! Command not implemented.');
                }
            }
            $class::instance()->execute($options);
            return;
        }

        if ($args = $options->getArgs()) {
            $recipe = $args[0];
            // Important - if we are upping a new recipe then we should unset the currently selected instance.
            \App\Service\Configurator::instance()->setMainConfigField('instance', null);
            $this->main->up($recipe);
            return;
        }

        $this->welcomeLine();

        if ($options->getOpt('installexec')) {
            $this->info('Installing to bin folder');
            \App\Service\InstallToBin::instance($this)->install();
        } else if ($options->getOpt('version')) {
            $this->info(self::$version);
        } else if ($options->getOpt('start')) {
            $this->main->startContainers();
        } else {
            echo $options->help();
        }
    }

    /**
     * Prompt user yes no, returning input OR returning callable depending on $onYes or $onNo.
     *
     * @param string $msg
     * @param callable|null $onYes
     * @param callable|null $onNo
     * @return mixed
     */
    public function promptYesNo(
        string $msg,
        ?callable $onYes = null,
        ?callable $onNo = null,
        string $default = 'n'
    ): mixed {
        $suffix = $default === 'y' ? '[Y/n]' : '[y/N]';
        $input = readline("$msg $suffix ");
        $input = trim($input);

        if ($input === '') {
            $input = $default;
        }

        $normalized = strtolower($input);

        if (in_array($normalized, ['y', 'yes'], true)) {
            return $onYes ? $onYes($input) : true;
        }

        return $onNo ? $onNo($input) : false;
    }

    /**
     * Reads user input from the command line and returns it.
     * Optionally displays a prompt message.
     * @param string $prompt
     * @return string
     */
    public function promptInput(string $prompt = ''): string {
        if ($prompt) {
            $input = readline($prompt);
        } else {
            $input = readline();
        }
        return $input === false ? '' : trim($input);
    }

    public function promptForOption(string $prompt, array $options): string {
        $this->info($prompt, []);
        foreach ($options as $index => $option) {
            $this->info(sprintf("%d) %s", $index + 1, $option), []);
        }
        while (true) {
            $input = $this->promptInput("Enter number (1-" . count($options) . "): ");
            $selection = intval($input);
            if ($selection > 0 && $selection <= count($options)) {
                return $options[$selection - 1];
            }
            $this->error("Invalid selection. Please try again.");
        }
    }

    public function debug($message, array $context = array()) {
        if (!$this->verbose) {
            return;
        }
        $this->log('debug', $message, $context);
    }

}
