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
                $executablePath = $dir . '/php';
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

        // Check if the user's home bin folder is in the $PATH
        $userBinDir = getenv('HOME') . '/bin';
        if (in_array($userBinDir, $dirs)) {
            $binDir = $userBinDir;
        } else if (in_array('/usr/local/bin', $dirs)) {
            $binDir = '/usr/local/bin';
        } else {
            throw new Exception('Could not find a suitable bin directory for installation.');
        }

        $contents = file_get_contents(__DIR__.'/../../mchef.php');
        $phpPath = $this->get_php_executable_path();
        $contents = '#!'.$phpPath."\n".$contents;
        $installFilePath = __DIR__.'/../../bin/mchef.php';
        file_put_contents($installFilePath, $contents);
        chmod($installFilePath, 0750);
        $binFilePath = $binDir.'/mchef.php';
        if (file_exists($binFilePath)) {
            try {
                unlink($binFilePath);
            } catch (\Exception $e) {
                throw new Exception('Unable to install to bin dir as it already exists there - ' . $binDir . '/mchef.php');
            }
        }

        $this->exec('sudo ln -s '.$installFilePath.' '.$binDir.'/mchef.php');

        $this->cli->success('Success! mchef.php has successfully been installed to '.$installFilePath);
        $this->cli->success('Open a brand new terminal and you should be able to call mchef.php directly (i.e. no need to prefix with php)');

    }
}