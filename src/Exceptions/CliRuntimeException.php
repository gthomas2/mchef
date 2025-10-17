<?php

namespace App\Exceptions;

use Throwable;

class CliRuntimeException extends \RuntimeException {
    protected array | null $info;
    
    public function __construct($message = "", $code = 0, Throwable|null $previous = null, array | null $info = [] ) {
        parent::__construct($message, $code, $previous);
        $this->info = $info;
    }

    public function getInfo(): array | null {
        return $this->info;
    }
}