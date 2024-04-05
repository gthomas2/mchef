<?php

namespace App\Tests\Fixtures\TestModels;

class Artist extends \App\Model\AbstractModel {
    public function __construct(
        public string $name,
        public bool $isBand
    ){}
}