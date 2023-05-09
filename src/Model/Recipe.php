<?php

namespace App\Model;

class Recipe extends AbstractModel {

    /**
     * @var string - recipe name.
     */
    public $name;

    /**
     * @var string - recipe version.
     */
    public $version;

    /**
     * @var string - recipe vendor name.
     */
    public $vendor;

    /**
     * @var string - e.g. 4.1.0
     */
    public $moodleTag;

    /**
     * @var string - e.g. 8.0
     */
    public $phpVersion;

    /**
     * @var array
     */
    public $plugins;

    /**
     * @var bool - if true, clone repository plugins to cwd/plugins
     */
    public $cloneRepoPlugins = false;

    /**
     * @var string - web host (leave blank for default of localhost).
     */
    public $host;

    /**
     * @var int - web port (leave blank for default of 8080).
     */
    public $port;

    /**
     * @var bool - set to true to add "host" to /etc/hosts if not present.
     */
    public $addSystemHost;

    /**
     * @var int - maximum upload size via php.
     */
    public $maxUploadSize;

    /**
     * @var int - maximum execution time.
     */
    public $maxExecTime;

    /**
     * @var string - database type - e.g. pgsql / mysql
     */
    public $dbType;

    /**
     * @var string - database version - e.g 8
     */
    public $dbVersion;

    /**
     * @var string - database user
     */
    public $dbUser;

    /**
     * @var string - database password
     */
    public $dbPassword;

    /**
     * @var string - database root password
     */
    public $dbRootPassword;

    /**
     * @var string - custom name for database container
     */
    public $dbContainerName;

    /**
     * @var string - custom name for moodle container
     */
    public $moodleContainerName;

    /**
     * @var bool - include php unit configuration
     */
    public $includePHPUnit;

    /**
     * @var bool - include behat configuration
     */
    public $includeBehat;

    /**
     * @var int - behat port
     */
    public $behatPort;

    /**
     * @var string - behat host
     */
    public $behatHost;

    /**
     * @var boolean - debug
     */
    public $debug;
}
