<?php

namespace App\Model;

class GlobalConfig extends AbstractModel {

    public function __construct(
        /**
         * Proxy the site so that it can be accessed on port 80 locally.
         * @var bool
         */
        public ?bool $useProxy = false,

        /**
         * Defaults the language for installs to this lang
         * @var string
         */
        public ?string $lang = 'en',
    ) {}
}
