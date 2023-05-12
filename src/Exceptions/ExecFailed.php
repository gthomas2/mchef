<?php

namespace App\Exceptions;

use Throwable;

class ExecFailed extends \Exception {
    protected $cmd;

    public function __construct($message = "", $code = 0, string $cmd, Throwable $previous = null) {
        $this->cmd = $cmd;
        parent::__construct($message, $code, $previous);
    }

    public function getCmd() {
        return $this->cmd;
    }
}