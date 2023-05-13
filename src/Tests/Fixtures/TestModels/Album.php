<?php

namespace App\Tests\Fixtures\TestModels;

class Album extends \App\Model\AbstractModel {
    public function __construct(
        public string $title,
        public string $year,
        public Artist $artist,
        /**
         * @var Track[]
         */
        public array $tracks
    ) {}
}