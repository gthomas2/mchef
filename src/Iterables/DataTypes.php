<?php

namespace App\Iterables;

use App\Model\DataType;

class DataTypes implements Iterator {

    /**
     * @var DataType[]
     */
    private array $types;
    public function __construct(array $types) {
        $this->types = $types;
    }
}