<?php

namespace App\Database;

use App\MChefCLI;
use App\Model\Recipe;

abstract class AbstractDatabase implements DatabaseInterface {
    public function __construct(Recipe $recipe, MChefCLI $cli) {
        $this->cli = $cli;
        $this->recipe = $recipe;
    }

    public function getDbName(): string {
        return ($this->recipe->containerPrefix ?? 'mc').'-moodle';
    }

    public function wipe(): void {
        throw new NotImplementedException(__METHOD__);
    }

    public function dbeaverConnectionString(): string {
        throw new NotImplementedException(__METHOD__);
    }
}
