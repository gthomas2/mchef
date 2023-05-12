<?php

namespace App\Model;

class Plugin extends AbstractModel {
    public function __construct(
        /**
         * @var string - franken style component name
         */
        public string $component,

        /**
         * @var string - path
         */
        public string $path,

        /**
         * @var string - path to plugin directory
         */
        public string $fullPath,

        /**
         * @var Volume
         */
        public Volume $volume
    ) {}
}
