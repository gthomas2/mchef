<?php

namespace App\Service;

use splitbrain\phpcli\CLI;

class AbstractService {
    /**
     * @var CLI
     */
    protected $cli;

    protected function __construct() {
        // Singleton.
    }

    public function set_cli(CLI $cli) {
        $this->cli = $cli;
    }

    protected static function get_instance(?CLI $cli = null): AbstractService {
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
