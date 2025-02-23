<?php

namespace App\Service;

use App\Helpers\OS;
use App\Traits\ExecTrait;
use splitbrain\phpcli\Exception;
use splitbrain\phpcli\CLI;

class InstallToBin extends AbstractService {
    use ExecTrait;

    final public static function instance(CLI $cli): InstallToBin {
        return self::setup_instance($cli);
    }

    protected function get_php_executable_path() {
        $phpExecutablePath = '';

        // Try to get the PHP executable path from the PHP_BINARY constant
        if (defined('PHP_BINARY')) {
            $phpExecutablePath = PHP_BINARY;
        }

        // If the PHP_BINARY constant is not defined, try to find the PHP executable path from the PATH environment variable
        if (empty($phpExecutablePath)) {
            $path = getenv('PATH');
            $dirs = explode(':', $path);

            foreach ($dirs as $dir) {
                $executablePath = $dir . 'DIRECTORY_SEPARATOR .  "php" . DIRECTORY_SEPARATOR';
                if (is_executable($executablePath)) {
                    $phpExecutablePath = $executablePath;
                    break;
                }
            }
        }

        return $phpExecutablePath;
    }

    public function install() {
        // Get the list of directories in the $PATH environment variable
        $path = getenv('PATH');
        $dirs = explode(':', $path);

        $contents = file_get_contents(OS::path("/../../mchef.php"));
        $phpPath = $this->get_php_executable_path();
        $contents = '#!'.$phpPath."\n".$contents;
        $installFilePath = OS::path(__DIR__.'/../../bin/mchef.php');
        file_put_contents($installFilePath, $contents);
        chmod($installFilePath, 0750);

        // Check if the user's home bin folder is in the $PATH
        $userBinDir = OS::path(getenv('HOME') . '/bin');
        if (in_array($userBinDir, $dirs)) {
            $binDir = $userBinDir;
        } else if (in_array(OS::path('/usr/local/bin'), $dirs)) {
            $binDir = OS::path('/usr/local/bin');
        } else {
            throw new Exception('Could not find a suitable bin directory for installation.');
        }

        $binFilePath = OS::path("$binDir/mchef.php");
        if (file_exists($binFilePath)) {
            try {
                unlink($binFilePath);
            } catch (\Exception $e) {
                throw new Exception('Unable to install to bin dir as it already exists there - ' . OS::path("$binDir/mchef.php"));
            }
        }

        if (OS::isWindows()) {
            symlink($installFilePath, $binFilePath);
        } else {
            $this->exec('sudo ln -s ' . $installFilePath . ' ' . $binFilePath);
        }

        $this->cli->success('Success! mchef.php has successfully been installed to '.$installFilePath);
        $this->cli->success('Open a brand new terminal and you should be able to call mchef.php directly (i.e. no need to prefix with php)');
    }
}
