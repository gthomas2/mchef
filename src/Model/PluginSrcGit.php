<?php

namespace App\Model;

class PluginSrcGit extends AbstractPluginSrc {
    public function __construct(
        /**
         * @var string - url to repository
         */
        public string $url,

        /**
         * @var string - branch, optional - null if not specified
         */
        public ?string $branch
    ) {}

    public function getRecipeSrc(): string {
        return $this->url.'~'.$this->branch;
    }
}
