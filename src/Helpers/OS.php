<?php

namespace App\Helpers;

use App\Exceptions\CodingException;

class OS {
    public static function isWindows(): bool {
        return stripos(PHP_OS, 'WIN') === 0;
    }

    public static function path(string $path): string {
        if (strpos($path, '\\') !== false) {
            throw new CodingException('Paths must never contain windows directory separators in code');
        }
        if (DIRECTORY_SEPARATOR === '/') {
            return $path;
        }
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
