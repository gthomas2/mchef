<?php

namespace App\Service;

use App\PDO\Postgres;
use App\Traits\WithRecipe;
use PDO;

enum DatabaseType: string {
    case Postgres = 'pgsql';
    case Mysql = 'mysql';
}

class Database extends AbstractService {

    use WithRecipe;

    protected static PDO $pdoref;

    protected function __construct() {
        $recipe = $this->getParsedRecipe();
        $dbType = $recipe->dbType;
        if (!DatabaseType::tryFrom($dbType)) {
            throw new \Exception('Unsupported dbType '.$dbType);
        }

        if ($dbType === DatabaseType::Postgres->value) {
            self::$pdoref = new Postgres();
        } else if ($dbType === DatabaseType::Mysql) {
            // TODO
            throw new \Exception('Not implemented yet '.$dbType);
        }
    }

    final public static function instance(): Database {
        return self::setup_instance();
    }

    private static function pdo(): PDO {
        self::instance();
        return self::$pdoref;
    }

    public static function query(string $query, ...$args)  {
        $query = trim($query);
        $statement = self::pdo()->prepare($query);
        if (!$statement) {
            throw new \Exception('Failed to parse query '.$query);
        }

        $success = $statement->execute($args);
        if (!$success) {
            throw new \Exception('Database query failed ' . $query . ' ' . var_export($args, true));
        }
        return $statement;
    }
}