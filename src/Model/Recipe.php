<?php

namespace App\Model;

class Recipe extends AbstractModel {

    public function __construct(
        /**
         * @var string - e.g. 4.1.0
         */
        public string $moodleTag,

        /**
         * @var string - e.g. 8.0
         */
        public string $phpVersion,

        /**
         * @var string - recipe name.
         */
        public ?string $name = null,

        /**
         * @var string - recipe version.
         */
        public ?string $version = null,

        /**
         * @var string - recipe vendor name.
         */
        public ?string $vendor = null,

        /**
         * @var array
         */
        public ?array $plugins = null,

        /**
         * @var bool - if true, clone repository plugins to cwd/plugins
         */
        public bool $cloneRepoPlugins = false,

        /**
         * @var string - web host (leave blank for default of localhost).
         */
        public ?string $host = null,

        /**
         * @var string - http or https.
         */
        public string $hostProtocol = 'http',

        /**
         * @var int - web port (leave blank for default of 8080).
         */
        public ?int $port = null,

        /**
         * @var bool - set to true to add "host" to /etc/hosts if not present.
         */
        public ?bool $updateHostHosts = null,

        /**
         * @var int - maximum upload size via php.
         */
        public ?int $maxUploadSize = null,

        /**
         * @var int - maximum execution time.
         */
        public ?int $maxExecTime = null,

        /**
         * @var string - database type - e.g. pgsql / mysql
         */
        public ?string $dbType = null,

        /**
         * @var string - database version - e.g 8
         */
        public ?string $dbVersion = null,

        /**
         * @var string - database user
         */
        public ?string $dbUser = null,

        /**
         * @var string - database password
         */
        public ?string $dbPassword = null,

        /**
         * @var string - database root password
         */
        public ?string $dbRootPassword = null,

        /**
         * Prefix for containers.
         * Default is 'mc'.
         * Setting this to 'fred' would result in the following containers:
         * fred-db
         * fred-moodle
         * fred-behat
         * @var string|null
         */
        public ?string $containerPrefix = 'mc',

        /**
         * @var bool - debug
         */
        public bool $debug = false,

        /**
         * @var bool - developer mode (installs more tools to docker image).
         */
        public bool $developer = false,

        /**
         * @var bool - include php unit configuration
         */
        public bool $includePHPUnit = false,

        /**
         * @var bool - include behat configuration
         */
        public bool $includeBehat = false,

        /**
         * @var string - behat host
         */
        public ?string $behatHost = null,

        // The following properties are set automatically on parse.

        public ?string $wwwRoot = null,

        public ?string $behatWwwRoot = null,
    ) {}
}
