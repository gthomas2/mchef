<?php

namespace App\Exceptions;

use Throwable;

class CliRuntimeException extends \RuntimeException {
    protected array $info;

    public function __construct($message = "", $code = 0, Throwable|null $previous = null, array $info = [] ) {
        parent::__construct($message, $code, $previous);
        $this->info = $info;
    }

    public function getInfo(): array | null {
        return $this->info;
    }
}