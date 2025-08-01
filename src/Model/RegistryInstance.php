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

        /**
         * @var string - container prefix from recipe.
         */
        public string $containerPrefix,

        /**
         * @var int|null - proxy mode port allocation (8100+)
         */
        public ?int $proxyModePort = null,

    ) {}
}
