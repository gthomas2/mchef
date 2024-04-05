<?php

namespace App\Model;

use splitbrain\phpcli\Exception;

abstract class AbstractPluginSrc extends AbstractModel {
    abstract public function getRecipeSrc(): string;
}

