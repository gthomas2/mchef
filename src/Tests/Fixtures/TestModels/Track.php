<?php

namespace App\Tests\Fixtures\TestModels;

class Track extends \App\Model\AbstractModel {
    public function __construct(
        public string $name,
        public string $length
    ){}
}