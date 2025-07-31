<?php

namespace App\Service;
use App\Helpers\OS;
use App\Traits\ExecTrait;
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Exception;

class Dependencies extends AbstractService {

    use ExecTrait;
    private $pluginsService;

    final public static function instance(?CLI $cli = null): Dependencies {
        return self::setup_singleton($cli);
    }

    private function dockerIsInstalled(): bool {
        // Use *nix 'which' or Windows PowerShell to check if docker is installed
        if (OS::isWindows()) {
            // Windows: use PowerShell to check for docker.exe
            $cmd = 'powershell -Command "Get-Command docker.exe"';
        } else {
            // Unix-like: use 'which docker'
            $cmd = 'which docker';
        }
        exec($cmd, $output, $returnVar);
        return $returnVar === 0 && !empty($output);
    }

    public function check(): void {
      $failed = false;

      if (!$this->dockerIsInstalled()) {
          $this->cli->error('Docker does not appear to be installed');
          $failed = true;
      }

      if (!$failed) {
          $this->cli->debug('Checking your docker version');
          $cmd = "docker version";
          try {
              $this->exec($cmd, "Error - Is your docker daemon running?");
          } catch (\Exception $ex) {
              $this->cli->error($ex);
              $failed = true;
          }
      }

      if (!$failed) {
          $this->cli->debug('Checking your docker compose version');
          try {
              $cmd = "docker compose version -f json";
              preg_match(
                  "/(?:version|v)\s*((?:[0-9]+\.?)+)/i",
                  json_decode(
                      $this->exec($cmd, "Error - Is docker compose installed?")
                  )->version,
                  $matches
              );
              $dcVersion = explode('.', $matches[1])[0];
              if (intval($dcVersion) < 2) {
                  $this->cli->error("docker compose version >= 2.x required");
                  $failed = true;
              }
          } catch (\Exception $ex) {
              $this->cli->error($ex);
              $failed = true;
          }
      }

      $this->cli->debug('Checking your php version.');

      $phpParts = explode('.', phpversion(), 2);
      $major = intval($phpParts[0]);
      $point = intval($phpParts[1]);
      if ($major < 8 || $point < 2) {
          $this->cli->error('PHP 8.2+ supported - you are using '.phpversion());
          $failed = true;
      }

      if($failed) {
          $this->cli->error('Please correct your system errors and try again.');
          die;
      }
    }
}
