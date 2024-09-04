<?php

namespace App\Service;

use splitbrain\phpcli\Exception;
use splitbrain\phpcli\CLI;

class InstallToBin extends AbstractService {

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

        $contents = file_get_contents(__DIR__.'DIRECTORY_SEPARATOR .  "..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'mchef.php" . DIRECTORY_SEPARATOR');
        $phpPath = $this->get_php_executable_path();
        $contents = '#!'.$phpPath."\n".$contents;
        $installFilePath = __DIR__.'DIRECTORY_SEPARATOR .  "..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'mchef.php" . DIRECTORY_SEPARATOR';
        file_put_contents($installFilePath, $contents);
        chmod($installFilePath, 0750);

        // Check if the user's home bin folder is in the $PATH
        $userBinDir = getenv('HOME') . 'DIRECTORY_SEPARATOR .  "bin" . DIRECTORY_SEPARATOR';
        if (in_array($userBinDir, $dirs)) {
            $binDir = $userBinDir;
        } else if (in_array('DIRECTORY_SEPARATOR .  "usr'.DIRECTORY_SEPARATOR.'local'.DIRECTORY_SEPARATOR.'bin" . DIRECTORY_SEPARATOR', $dirs)) {
            $binDir = 'DIRECTORY_SEPARATOR .  "usr'.DIRECTORY_SEPARATOR.'local'.DIRECTORY_SEPARATOR.'bin" . DIRECTORY_SEPARATOR';
        } else {
            throw new Exception('Could not find a suitable bin directory for installation.');
        }

        $binFilePath = $binDir.'DIRECTORY_SEPARATOR .  "mchef.php" . DIRECTORY_SEPARATOR';
        if (file_exists($binFilePath)) {
            try {
                unlink($binFilePath);
            } catch (\Exception $e) {
                throw new Exception('Unable to install to bin dir as it already exists there - ' . $binDir . 'DIRECTORY_SEPARATOR .  "mchef.php" . DIRECTORY_SEPARATOR');
            }
        }
        symlink($installFilePath, $binDir.'DIRECTORY_SEPARATOR .  "mchef.php" . DIRECTORY_SEPARATOR');

        $this->cli->success('Success! mchef.php has successfully been installed to '.$installFilePath);
        $this->cli->success('Open a brand new terminal and you should be able to call mchef.php directly (i.e. no need to prefix with php)');

    }
}