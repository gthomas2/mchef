<?php

namespace App\Command;

use App\Database\DatabaseInterface;
use App\Database\Mysql;
use App\Database\Postgres;
use App\Model\Recipe;
use App\Model\RegistryInstance;
use App\Service\Main;
use App\StaticVars;
use App\Traits\ExecTrait;
use App\Traits\SingletonTrait;
use splitbrain\phpcli\Options;

class Database extends AbstractCommand {

    use SingletonTrait;
    use ExecTrait;

    // Service dependencies.
    private Main $mainService;

    // Other properties.
    private RegistryInstance $instance;
    private Recipe $recipe;
    private DatabaseInterface $database;

    // Constants.
    const COMMAND_NAME = 'database';

    final public static function instance(): Database {
        return self::setup_singleton();
    }

    private function execDatabase() {
        $this->cli->notice('TODO: Exec onto database...');
    }

    private function wipeDatabase() {
        $this->cli->promptYesNo("ADVICE: TAKE A BACKUP FIRST!\n".
            "Are you sure you want to wipe your db?",
            function() {
                try {
                    $this->database->wipe();
                } catch (\Throwable $e) {
                    throw new \RuntimeException('Failed to wipe database', previous: $e);
                }
                $this->cli->success('All tables should be wiped from database');
            }
        );
    }

    private function dbeaver() {
        $cmd = $this->database->dbeaverConnectionString();
        $this->exec($cmd);
    }

    private function info() {
        $dbName = ($this->recipe->containerPrefix ?? 'mc').'-moodle';
        $dbContainer = ($this->recipe->containerPrefix ?? 'mc').'-db';
        $localPortInfo = $this->recipe->dbHostPort ? " Local port = {$this->recipe->dbHostPort} " : '';
        $this->cli->info("Container = $dbContainer{$localPortInfo}Database = $dbName User {$this->recipe->dbUser} Password {$this->recipe->dbPassword}");
    }

    private function resolveDatabase(): DatabaseInterface {
        return match ($this->recipe->dbType) {
            'pgsql' => new Postgres($this->recipe, $this->cli),
            'mysql' => new Mysql($this->recipe, $this->cli),
            default => throw new \InvalidArgumentException(
                "Unsupported database type {$this->recipe->dbType}"
            ),
        };
    }

    public function execute(Options $options): void {
        try {
            $this->setStaticVarsFromOptions($options);
        } catch (\RuntimeException $e) {
            $this->cli->error($e->getMessage());
            exit(1);
        }
        $this->instance = StaticVars::$instance;

        $this->database = $this->resolveDatabase();
        $this->recipe = $this->mainService->getRecipe($this->instance->recipePath);
        $this->database = $this->resolveDatabase();

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
        if (!empty($options->getOpt('dbeaver'))) {
            $this->dbeaver();
            return;
        }
        $this->cli->error('You must specify an option - e.g. --exec, --wipe, --info');
    }

    public function register(Options $options): void {
        $options->registerCommand(self::COMMAND_NAME, 'Database utilities');
        $options->registerOption('exec', 'Exec onto database', 'e', false, self::COMMAND_NAME);
        $options->registerOption('wipe', 'Wipe database', 'w', false, self::COMMAND_NAME);
        $options->registerOption('info', 'Get db connection info', 'i', false, self::COMMAND_NAME);
        $options->registerOption('dbeaver', 'Open in dbeaver', 'b', false, self::COMMAND_NAME);
    }
}
