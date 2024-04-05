<?php

namespace App\Enums;

/**
 * Scalar and basic data types.
 */
enum TYPE: string {
    case string = 'string';
    case int = 'integer';
    case bool = 'boolean';
    case array = 'array';
    case float = 'float';
    case object = 'object';
}
