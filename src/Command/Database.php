<?php

namespace App\Command;

use App\Exceptions\ExecFailed;
use App\Model\Recipe;
use App\Model\RegistryInstance;
use App\Service\Docker;
use App\Service\Main;
use App\StaticVars;
use App\Traits\ExecTrait;
use App\Traits\SingletonTrait;
use splitbrain\phpcli\Options;
use MChefCLI;

class Database extends AbstractCommand {

    use SingletonTrait;
    use ExecTrait;

    protected RegistryInstance $instance;
    protected Recipe $recipe;

    const COMMAND_NAME = 'database';

    final public static function instance(MChefCLI $cli): Database {
        $instance = self::setup_singleton($cli);
        return $instance;
    }

    public function getDbName(): string {
        return ($this->recipe->containerPrefix ?? 'mc').'-moodle';
    }

    private function execDatabase() {
        $this->cli->notice('TODO: Exec onto database...');
    }

    private function wipePostgresDatabase() {
        $mainService = Main::instance($this->cli);
        $dbContainer = $mainService->getDockerDatabaseContainerName();
        $dockerService = Docker::instance($this->cli);
        $recipe = $this->recipe;
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

    private function wipeMysqlDatabase() {
        throw new \Exception('TODO!');
    }

    private function wipeDatabase() {
        $this->cli->promptYesNo("ADVICE: TAKE A BACKUP FIRST!\n".
            "Are you sure you want to wipe your db?",
            function() {
                try {
                    if ($this->recipe->dbType === 'pgsql') {
                        $this->wipePostgresDatabase();
                    } else if ($this->recipe->dbType === 'mysql') {
                        $this->wipeMysqlDatabase();
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
        $dbName = ($this->recipe->containerPrefix ?? 'mc').'-moodle';
        $dbContainer = ($this->recipe->containerPrefix ?? 'mc').'-db';
        $localPortInfo = $this->recipe->dbHostPort ? " Local port = $this->recipe->dbHostPort " : '';
        $this->cli->info("Container = $dbContainer{$localPortInfo}Database = $dbName User {$this->recipe->dbUser} Password {$this->recipe->dbPassword}");
    }

    public function execute(Options $options): void {
        $this->setStaticVarsFromOptions($options);
        $this->instance = StaticVars::$instance;
        $mainService = Main::instance($this->cli);
        $this->recipe = $mainService->getRecipe($this->instance->recipePath);

        if (!empty($options->getOpt('wipe'))) {
            $this->wipeDatabase();
            return;
        }
        if (!empty($options->getOpt('exec'))) {
            $this->execDatabase();
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
