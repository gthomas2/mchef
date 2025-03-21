<?php

namespace App\Helpers;

use App\Exceptions\CodingException;

class OS {
    public static function isWindows(): bool {
        return stripos(PHP_OS, 'WIN') === 0;
    }

    public static function escShellArg(string $arg): string {
        if (!self::isWindows()) {
            return escapeshellarg($arg);
        }
        return "'".str_replace("'", "''", $arg)."'";
    }

    public static function path(string $path): string {
        if (DIRECTORY_SEPARATOR === '/') {
            return $path;
        }
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
