<?php

namespace App\Exceptions;

use Throwable;

class NotImplementedException extends \Exception {
    public function __construct($method, $code = 0, Throwable $previous = null) {
        $message = 'The method '.$method.' has not been implemented for this class';
        parent::__construct($message, $code, $previous);
    }
}
