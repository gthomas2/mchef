<?php

namespace App\Model;

class Volume extends AbstractModel {
    public function __construct(
        /**
         * @var string - relative path
         */
        public string $path,

        /**
         * @var string - absolute path on host
         */
        public string $hostPath,
    ) {}
}
