<?php

namespace App\Database;

use App\Exceptions\ExecFailed;
use App\Helpers\OS;
use App\Service\Docker;
use App\Service\Main;

class Mysql extends AbstractDatabase implements DatabaseInterface {

    public function wipe(): void {
        // TODO: Implement MySQL wipe functionality
        throw new \RuntimeException('MySQL wipe functionality not yet implemented');
    }

    public function dbeaverConnectionString(): string {
        if (empty($this->recipe->dbHostPort)) {
            throw new \Error('The recipe must have dbHostPort specified in order to generate a connection string');
        }
        $dbeavercmd = OS::isWindows() ? 'dbeaver.exe' : 'open -na "DBeaver" --args';
        $conString = sprintf(
            'driver=mysql|host=localhost|port=%s|database=%s|user=%s|password=%s',
            $this->recipe->dbHostPort,
            $this->getDbName(),
            $this->recipe->dbUser,
            $this->recipe->dbPassword
        );

        // Escape the *whole thing once*
        $cmd = $dbeavercmd . ' -con ' . escapeshellarg($conString);
        return $cmd;
    }

    /**
     * Return a MySQL Workbench connection string (MySQL only).
     * @return string
     */
    public function mysqlWorkbenchConnectionString(): string {
        if (empty($this->recipe->dbHostPort)) {
            throw new \Error('The recipe must have dbHostPort specified in order to generate a connection string');
        }

        $workbenchCmd = OS::isWindows() ? 'MySQLWorkbench.exe' : 'open -a "MySQL Workbench"';
        $connectionString = sprintf(
            'mysql://%s:%s@localhost:%s/%s',
            urlencode($this->recipe->dbUser),
            urlencode($this->recipe->dbPassword),
            $this->recipe->dbHostPort,
            urlencode($this->getDbName())
        );

        return $workbenchCmd . ' --query=' . escapeshellarg($connectionString);
    }

    /**
     * Return a mysql CLI connection string (MySQL only).
     * @return string
     */
    public function mysqlConnectionString(): string {
        if (empty($this->recipe->dbHostPort)) {
            throw new \Error('The recipe must have dbHostPort specified in order to generate a connection string');
        }

        return sprintf(
            'mysql -h localhost -P %s -u %s -p%s %s',
            escapeshellarg($this->recipe->dbHostPort),
            escapeshellarg($this->recipe->dbUser),
            escapeshellarg($this->recipe->dbPassword),
            escapeshellarg($this->getDbName())
        );
    }

    public function pgAdminConnectionString(): string {
        throw new \InvalidArgumentException('pgAdmin can only be used with PostgreSQL databases');
    }

    public function psqlConnectionCommand(): array {
        throw new \InvalidArgumentException('psql can only be used with PostgreSQL databases');
    }

}
