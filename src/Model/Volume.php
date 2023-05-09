<?php

namespace App\Model;

class Volume extends AbstractModel {
    /**
     * @var string - relative path
     */
    public $path;

    /**
     * @var string - absolute path on host
     */
    public $hostPath;
}
