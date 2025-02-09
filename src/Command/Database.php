<?php

namespace App\Command;

use App\Exceptions\ExecFailed;
use App\Service\Docker;
use App\Service\Main;
use App\Traits\ExecTrait;
use App\Traits\SingletonTrait;
use App\Traits\WithRecipe;
use splitbrain\phpcli\Exception;
use splitbrain\phpcli\Options;
use MChefCLI;

class Database extends AbstractCommand {

    use SingletonTrait;
    use ExecTrait;
    use WithRecipe;

    const COMMAND_NAME = 'database';

    final public static function instance(MChefCLI $cli): Database {
        $instance = self::setup_instance($cli);
        return $instance;
    }

    public function getDbName(): string {
        $recipe = $this->getParsedRecipe();
        return ($recipe->containerPrefix ?? 'mc').'-moodle';
    }

    private function exec_database() {
        $this->cli->notice('TODO: Exec onto database...');
    }

    private function wipe_postgres_database() {
        $mainService = Main::instance($this->cli);
        $dbContainer = $mainService->getDockerDatabaseContainerName();
        $dockerService = Docker::instance($this->cli);
        $recipe = $this->getParsedRecipe();
        $dockerService->execute($dbContainer, "sh -c \"export PGPASSWORD=$recipe->dbPassword\"");
        $dbName = $this->getDbName();
        try {
            $dbDelCmd = 'psql -U ' . $recipe->dbUser . ' -d ' . $recipe->dbName .
                ' -c "DO \$\$ DECLARE row RECORD; BEGIN FOR row IN (SELECT tablename FROM pg_tables WHERE schemaname = \'public\') LOOP EXECUTE \'DROP TABLE IF EXISTS public.\' || quote_ident(row.tablename); END LOOP; END \$\$;"';
            $dockerService->execute($dbContainer, $dbDelCmd);
        } catch (ExecFailed) {
            // Delete them one at a time.
            $cmd = "SELECT table_name FROM information_schema.tables WHERE table_type='BASE TABLE' AND table_schema='public'";
            $cmdProcessed = escapeshellarg(str_replace("\n", ' ', $cmd));
            $tablesStr = $dockerService->execute($dbContainer, "psql -U $recipe->dbUser -d $dbName -c $cmdProcessed -t");
            $tables = explode("\n", $tablesStr);
            foreach ($tables as $table) {
                $table = trim($table);
                if (empty($table)) {
                    continue;
                }
                $this->cli->info('Attempting to wipe table '.$table);
                $cmd = "DROP TABLE $table CASCADE;";
                echo "\n".$cmd;
                $cmdProcessed = escapeshellarg(str_replace("\n", ' ', $cmd));
                $output = $dockerService->execute($dbContainer, "psql -U $recipe->dbUser -d $dbName -c $cmdProcessed -t");
                $this->cli->info($output);
            }
        }
    }

    private function wipe_mysql_database() {
        throw new \Exception('TODO!');
    }

    private function wipe_database() {
        $this->cli->promptYesNo("ADVICE: TAKE A BACKUP FIRST!\n".
            "Are you sure you want to wipe your db?",
            function() {
                $recipe = $this->getParsedRecipe();
                try {
                    if ($recipe->dbType === 'pgsql') {
                        $this->wipe_postgres_database();
                    } else if ($recipe->dbType === 'mysql') {
                        $this->wipe_mysql_database();
                    } else {
                        throw new \Exception('Invalid database type ' . $recipe->dbType);
                    }
                } catch (\Exception) {
                    throw new \Exception('Failed to wipe database');
                }
                $this->cli->success('All tables should be wiped from database');
            }
        );
    }

    private function info() {
        $mainService = Main::instance($this->cli);
        $recipe = $mainService->getRecipe();
        $dbName = ($recipe->containerPrefix ?? 'mc').'-moodle';
        $dbContainer = ($recipe->containerPrefix ?? 'mc').'-db';
        $localPortInfo = $recipe->dbHostPort ? " Local port = $recipe->dbHostPort " : '';
        $this->cli->info("Container = $dbContainer{$localPortInfo}Database = $dbName User $recipe->dbUser Password $recipe->dbPassword");
    }

    public function execute(Options $options): void {
        if (!empty($options->getOpt('wipe'))) {
            $this->wipe_database();
            return;
        }
        if (!empty($options->getOpt('exec'))) {
            $this->exec_database();
            return;
        }
        if (!empty($options->getOpt('info'))) {
            $this->info();
            return;
        }
        $this->cli->error('You must specify an option - e.g. --exec, --wipe, --info');
    }

    public function register(Options $options): void {
        $options->registerCommand(self::COMMAND_NAME, 'Database utilities');
        $options->registerOption('exec', 'Exec onto database', 'e', false, self::COMMAND_NAME);
        $options->registerOption('wipe', 'Wipe database', 'w', false, self::COMMAND_NAME);
        $options->registerOption('info', 'Get db connection info', 'i', false, self::COMMAND_NAME);
    }
}
