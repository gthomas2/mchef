<?php

namespace App\Traits;

use App\Interfaces\SingletonInterface;
use MChefCLI;

trait SingletonTrait {
    /**
     * @var MChefCLI
     */
    protected $cli;

    protected function __construct() {
        // Singleton.
    }

    public function set_cli(MChefCLI $cli) {
        $this->cli = $cli;
    }

    protected static function setup_instance(?MChefCLI $cli = null): SingletonInterface {
        static $instances = [];
        $class = get_called_class();
        if (!empty($instances[$class])) {
            return $instances[$class];
        }
        $instance = new $class();
        $instances[$class] = $instance;
        if ($instance && $cli) {
            $instance->set_cli($cli);
        }
        return $instance;
    }
}