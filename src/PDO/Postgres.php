<?php

namespace App\PDO;

use App\Service\Main;
use App\Traits\WithRecipe;

use PDO;

class Postgres extends PDO {
    use WithRecipe;
    public function __construct($options = null) {
        $recipe = $this->getParsedRecipe();
        if ($recipe->dbType !== 'pgsql') {
            throw new \Exception('Database type is not pgsql!');
        }

        $mainService = Main::instance();
        $dbContainer = $mainService->getDockerDatabaseContainerName();

        parent::__construct("pgsql:host=$dbContainer;port=5432;dbname=$recipe->dbName",
            $recipe->dbUser,
            $recipe->dbPassword, $options);
    }

}
