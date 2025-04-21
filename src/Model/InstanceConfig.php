<?php

namespace App\Model;

class InstanceConfig extends AbstractModel {

    public function __construct(
        /**
         * @var string - location of recipe
         */
        public string $recipePath,

        /**
         * Do not proxy this site if true.
         * @var bool
         */
        public bool $bypassProxy,

        /**
         * A randomized port to use with mchef reverse proxy.
         * @var int
         */
        public int $localport,

    ) {}
}
