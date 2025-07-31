<?php

namespace App\Traits;

use App\Model\Recipe;
use App\Service\Main;
use App\StaticVars;

trait WithRecipe {

    public function getParsedRecipe(?string $instanceName = null): Recipe {
        if (StaticVars::$recipe !== null) {
            return StaticVars::$recipe;
        }

        $mainService = Main::instance($this->cli);
        $instance = $mainService->resolveActiveInstance($instanceName);

        StaticVars::$recipe = $mainService->getRecipe($instance->recipePath ?? null);

        return StaticVars::$recipe;
    }
}
