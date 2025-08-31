<?php

namespace App\Database;

use App\Exceptions\ExecFailed;
use App\Helpers\OS;
use App\Service\Docker;
use App\Service\Main;

class Postgres extends AbstractDatabase implements DatabaseInterface {
    public function wipe(): void {
        $mainService   = Main::instance($this->cli);
        $dbContainer   = $mainService->getDockerDatabaseContainerName();
        $dockerService = Docker::instance($this->cli);
        $recipe        = $this->recipe;
        $dbUser        = $recipe->dbUser;
        $dbName        = $this->getDbName(); // single source of truth
        $env           = '-e PGPASSWORD=' . escapeshellarg($recipe->dbPassword);

        // Helper to run one SQL command via psql with strict/error-stop flags.
        $psql = function (string $sql) use ($dockerService, $dbContainer, $env, $dbUser, $dbName): string {
            $cmd = sprintf(
                "psql -U %s -d %s -v ON_ERROR_STOP=1 -q -t -A -c %s",
                escapeshellarg($dbUser),
                escapeshellarg($dbName),
                escapeshellarg($sql)
            );
            return $dockerService->execute($dbContainer, $cmd, $env);
        };

        // DO block: drop every existing table in public (no grant/privilege churn).
        $doBlock = <<<'SQL'
            DO $$
            DECLARE row RECORD;
            BEGIN
              FOR row IN
                (SELECT tablename FROM pg_tables WHERE schemaname = 'public')
              LOOP
                EXECUTE 'DROP TABLE IF EXISTS public.' || quote_ident(row.tablename) || ' CASCADE';
              END LOOP;
            END
            $$;
        SQL;

        try {
            $psql($doBlock);
            return;
        } catch (ExecFailed) {
            $this->cli->warning('DO block failed; falling back to per-table drops…');
        }

        // Fallback: enumerate base tables and drop one-by-one (quoted, with CASCADE).
        try {
            $listSql = "SELECT tablename FROM pg_tables WHERE schemaname='public'";
            $tablesStr = $psql($listSql);
            $tables = array_filter(array_map('trim', explode("\n", $tablesStr)));

            foreach ($tables as $table) {
                // Quote identifiers safely using format('%I.%I', …) inside PG
                $dropSql = sprintf(
                    "DO $$ BEGIN EXECUTE 'DROP TABLE IF EXISTS ' || format('%%I.%%I','public',%s) || ' CASCADE'; END $$;",
                    // pass table name as a literal; PG will quote identifier via format('%I')
                    sprintf("'%s'", str_replace("'", "''", $table))
                );
                $this->cli->info(sprintf('Dropping table %s…', $table));
                $psql($dropSql);
            }
        } catch (ExecFailed $e) {
            throw new \RuntimeException('Failed to wipe database', 0, $e);
        }
    }

    public function dbeaverConnectionString(): string {
        if (empty($this->recipe->dbHostPort)) {
            throw new \Error('The recipe must have dbHostPort specified in order to generate a connection string');
        }
        $dbeavercmd = OS::isWindows() ? 'dbeaver.exe' : 'open -na "DBeaver" --args';
        $conString = sprintf(
            'driver=postgresql|host=localhost|port=%s|database=%s|user=%s|password=%s',
            $this->recipe->dbHostPort,
            $this->getDbName(),
            $this->recipe->dbUser,
            $this->recipe->dbPassword
        );

        // Escape the *whole thing once*
        $cmd = $dbeavercmd . ' -con ' . escapeshellarg($conString);
        return $cmd;
    }
}
