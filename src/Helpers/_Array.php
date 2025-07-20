<?php

namespace App\Helpers;

class _Array {
    /**
     * Case-insensitive search for needle in haystack.
     * @param $needle
     * @param $haystack
     * @return bool
     */
    public static function includes($needle, array $haystack) {
        return in_array(strtolower($needle), array_map('strtolower', $haystack));
    }
}
