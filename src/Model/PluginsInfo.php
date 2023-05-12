<?php

namespace App\Model;

class PluginsInfo extends AbstractModel {
    public function __construct(
        /**
         * @var Volume[]
         */
        public array $volumes,

        /**
         * @var Plugin[]
         */
        public array $plugins,
    ) {}
}
