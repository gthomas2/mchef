<?php

namespace App\Service;
use App\Traits\ExecTrait;
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Exception;

class Dependencies extends AbstractService {

    use ExecTrait;
    private $pluginsService;

    final public static function instance(?CLI $cli = null): Dependencies {
        return self::setup_instance($cli);
    }

    public function check(): void {
      $failed = false;

      $this->cli->notice('Checking your docker version');
      $cmd = "docker version";
      try {
        $this->exec($cmd, "Error - Is docker installed?");
      } catch(\Exception $ex) {
        $this->cli->error($ex);
        $failed = true;
      }

      $this->cli->notice('Checking your docker compose version');
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
        if(intval($dcVersion) < 2) {
          $this->cli->error("docker compose version >= 2.x required");
          $failed = true;
        }
      } catch(\Exception $ex) {
        $this->cli->error($ex);
        $failed = true;
      }

      $this->cli->notice('Checking your php version.');

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
