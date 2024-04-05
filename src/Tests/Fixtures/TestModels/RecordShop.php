<?php

namespace App\Tests\Fixtures\TestModels;

use App\Attributes\ArrayOf;

class RecordShop extends \App\Model\AbstractModel {
    public function __construct(
        #[ArrayOf(Album::class)]
        public array $albums
    ){}
}