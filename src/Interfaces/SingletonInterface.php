<?php

namespace App\Interfaces;

use App\MChefCLI;

Interface SingletonInterface {

    /**
     * Set cli on class cli property.
     *
     * @param CLI $cli
     * @return mixed
     */
    function set_cli(MChefCLI $cli);

}
