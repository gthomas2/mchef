<?php

namespace App\Traits;

use App\Model\Recipe;
use App\Service\Main;

trait WithRecipe {
    public function getParsedRecipe(): Recipe {
        static $recipe = null;

        if ($recipe !== null) {
            return $recipe;
        }

        static $mainService = null;
        if (!$mainService) {
            $mainService = Main::instance($this->cli);
        }

        return $mainService->getRecipe();
    }
}