<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

$vendor_path = __DIR__.'/vendor/autoload.php';
if (stripos(__FILE__, 'bin/')) {
    $vendor_path = __DIR__ . '/../vendor/autoload.php';
} 

if (!file_exists($vendor_path)) {
    echo 'Please run composer install first!';
    die;
}

require $vendor_path;

use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

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

    private function registerCommands(Options $options) {
        if (strpos(__FILE__, 'bin/mchef.php') !== false) {
            $files = scandir(__DIR__.'/../src/Command');
        } else {
            $files = scandir(__DIR__.'/src/Command');
        }

        $files = array_filter($files, function($file) {
            return strpos($file, '.') !== 0 && strpos($file, 'AbstractCommand') !== 0;
        });
        foreach ($files as $file) {
            $class = 'App\\Command\\'.ucfirst(basename($file, '.php'));
            if (class_exists($class)) {
                $class::instance($this)->register($options);
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
        $options->registerOption('halt', 'Stop all containers associated with this recipe', 'h');
        $options->registerOption('installexec', 'Install executable version of mchef.php to users bin folder', 'i');
        $options->registerOption('version', 'Print version', 'v');
    }

    protected function main(Options $options) {
        if ($cmd = $options->getCmd()) {
            $class = 'App\\Command\\'.ucfirst($cmd);
            if (!class_exists($class)) {
                throw new \splitbrain\phpcli\Exception('Invalid command! Command not implemented.');
            }
            $class::instance($this)->execute($options);
            return;
        }

        $this->main = \App\Service\Main::instance($this);

        if ($args = $options->getArgs()) {
            $recipe = $args[0];
            $this->main->create($recipe);
            return;
        }
        if ($options->getOpt('installexec')) {
            $this->info('Installing to bin folder');
            \App\Service\InstallToBin::instance($this)->install();
        } else if ($options->getOpt('version')) {
            $this->info(self::$version);
        } else if ($options->getOpt('start')) {
            $this->main->startContainers();
        } else if ($options->getOpt('halt')) {
            $this->main->stopContainers();
        } else {
            echo $options->help();
        }
    }

    /**
     * Prompt user yes no, returning input OR returning callable depending on $onYes or $onNo.
     * @param string $msg
     * @param callable|null $onYes
     * @param callable|null $onNo
     * @return mixed
     */
    public function promptYesNo(string $msg, ?callable $onYes = null, ?callable $onNo = null) {
        $msg .= ' [y/N] ';
        $input = readline($msg);
        if (trim(strtolower($input)) === 'y' || trim(strtolower($input)) === 'yes') {
            if ($onYes) {
                return $onYes($input);
            }
            return;
        }
        if ($onNo) {
            return $onNo($input);
        }
        return $input;
    }
}

$cli = new MChefCLI();
$cli->run();
