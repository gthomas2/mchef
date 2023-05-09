<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

if (stripos(__FILE__, 'bin/')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    require __DIR__ . '/vendor/autoload.php';
}


use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

class MRecipe extends CLI {
    static $version = '1.0.0';

    protected function setup(Options $options) {
        $options->setHelp('Facilitates the creation of moodle docker instances with custom configurations.');
        $options->registerOption('version', 'print version', 'v');
        $options->registerArgument('recipe', 'File location of recipe', false);
        $options->registerOption('installexec', 'Install executable version of mchef.php to users bin folder', 'i');
    }

    protected function main(Options $options) {
        if ($args = $options->getArgs()) {
            $recipe = $args[0];
            (\App\Service\Main::instance($this))->create($recipe);
            return;
        }
        if ($options->getOpt('installexec')) {
            $this->info('Installing to bin folder');
            \App\Service\InstallToBin::instance($this)->install();
        } else if ($options->getOpt('version')) {
            $this->info(self::$version);
        } else {
            echo $options->help();
        }
    }
}

$cli = new MRecipe();
$cli->run();