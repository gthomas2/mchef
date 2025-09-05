<?php

namespace App\Tests;

use App\MChefCLI;
use splitbrain\phpcli\Options;

class TestCLI extends MChefCLI {
    private $color = true;

    public function debug($message, array $context = array()) {
        // noop
    }

    public function info($message, array $context = array()) {
        // noop
    }

    public function error($message, array $context = array()) {
        // noop
    }

    public function notice($message, array $context = array()) {
        // noop
    }

    public function promptInput(string $prompt = ''): string {
        return '';
    }

    public function promptYesNo(string $msg, ?callable $onYes = null, ?callable $onNo = null, string $default = 'n'): mixed {
        return true;
    }

    public function setOption($name, $value) {
        $this->options[$name] = $value;
    }

    protected function setup(Options $options) {
        // noop
    }

    protected function main(Options $options) {
        // noop
    }
}
