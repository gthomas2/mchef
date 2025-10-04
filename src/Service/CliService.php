<?php

namespace App\Service;
use App\Helpers\OS;

/**
 * Service facilitating CLI but NOT the CLI (See MChefCLI for the actual CLI)
 */
class CliService extends AbstractService {

    final public static function instance(): CliService {
        return self::setup_singleton();
    }

    public function locateCommandClass(string $command): ?string {
        $commandDir = OS::path(__DIR__.'/../Command');
        $files = scandir($commandDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === 'AbstractCommand.php') {
                continue;
            }
            $baseName = pathinfo($file, PATHINFO_FILENAME);
            $class = "App\\Command\\".$baseName;
            $cmdName = $class::COMMAND_NAME;
            if ($cmdName === $command) {
                return $class;
            }
        }
        return null;
    }
}
