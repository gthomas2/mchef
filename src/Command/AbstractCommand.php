<?php

namespace App\Command;

use App\Interfaces\SingletonInterface;
use App\Traits\SingletonTrait;
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
}
