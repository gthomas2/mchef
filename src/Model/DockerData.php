<?php

namespace App\Model;

class DockerData extends Recipe {
    /**
     * @var array
     */
    public $volumes;

    /**
     * @var string
     */
    public $dockerFile;
}
