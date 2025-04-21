<?php

namespace App\Model;

class RegistryInstance extends AbstractModel {

    public function __construct(
        /**
         * Uuid used to registry this instance.
         * @var bool
         */
        public string $uuid,

        /**
         * Recipe path.
         * @var string
         */
        public string $recipePath,

    ) {}
}
