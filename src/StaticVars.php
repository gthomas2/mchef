<?php

namespace App;

use App\Model\Recipe;
use App\Model\RegistryInstance;

class StaticVars {
    static ?RegistryInstance $instance;
    static ?Recipe $recipe;
}
