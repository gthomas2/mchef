<?php

namespace App\Model;

class RecipePlugin extends AbstractModel {
    public function __construct(
        /**
         * @var string - repository URL (mandatory)
         */
        public string $repo,

        /**
         * @var string - branch name (optional, defaults to 'master')
         */
        public string $branch = 'master',

        /**
         * @var string|null - upstream repository URL (optional)
         */
        public ?string $upstream = null
    ) {}
}
