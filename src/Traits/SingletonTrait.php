<?php

namespace App\Traits;

use App\Interfaces\SingletonInterface;
use MChefCLI;
use PHPUnit\Framework\MockObject\MockObject;

trait SingletonTrait {
    /**
     * @var MChefCLI
     */
    protected $cli;

    protected function __construct() {
        // Singleton.
    }

    public function set_cli(MChefCLI|MockObject $cli) {
        $this->cli = $cli;
    }

    protected static function setup_instance(MChefCLI|MockObject|null $cli = null): SingletonInterface {
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
