<?php

namespace App\Model;

class Plugin extends AbstractModel {
    const TYPE_SINGLE = 'TYPE_SINGLE';
    const TYPE_COLLECTION = 'TYPE_COLLECTION';

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
        public Volume $volume,

        /**
         * @var string How the plugin was defined in the recipe - e.g. "https://github.com/gthomas2/moodle-filter_imageopt"
         */
        public string $recipeSrc,

        /**
         * @var string TYPE_SINGLE | TYPE_COLLECTION
         */
        public string $srcType = 'TYPE_SINGLE'
    ) {}
}
