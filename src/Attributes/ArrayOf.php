<?php

namespace App\Attributes;
use Attribute;
use App\Enums\TYPE;
use splitbrain\phpcli\Exception;

// #[ArrayOf(TYPE::string)] - An array of strings
// #[ArrayOf(TYPE::int)] - An array of integers
// #[ArrayOf(CustomClass::class)] - An array of CustomClass objects
// #[ArrayOf(TYPE::string, TYPE::bool, EmployeeRecord::class)] - An array where each member can either be a string, bool, or an EmployeeRecord object.
#[Attribute(Attribute::TARGET_PROPERTY|Attribute::TARGET_PARAMETER|Attribute::TARGET_METHOD)]
class ArrayOf {

    // All acceptable types.
    public string|TYPE|array $types;

    public function __construct(string|TYPE...$types) {
        $this->types = [];
        foreach ($types as $type) {
            if (!$type instanceof TYPE) {
                // Allow scalar type names
                $scalarTypes = ['string', 'int', 'integer', 'bool', 'boolean', 'float', 'double', 'array', 'object'];
                if (!in_array($type, $scalarTypes) && !class_exists($type)) {
                    throw new \Error("Attribute error: Invalid type / the class $type does not exist");
                }
            }
            $this->types[] = $type;
        }
    }

    public function validate($values) {
        if (!is_array($values)) {
            throw new Exception('Value is not an array');
        }
        foreach ($values as $value) {
            $valueok = false;
            foreach ($this->types as $type) {
                if ($type instanceof TYPE) {
                    $checktype = $type->value;
                    if (gettype($value) === $checktype) {
                        $valueok = true;
                        break;
                    }
                }
                if (is_string($value) && class_exists($type) && get_class($value) === $type) {
                    $valueok = true;
                    break;
                }
            }
            if (!$valueok) {
                throw new Exception('Value is invalid as per defined types allowed in array');
            }
        }
    }
}


