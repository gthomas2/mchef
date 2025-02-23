<?php

namespace App\Service;
use splitbrain\phpcli\CLI;

class CentralConfig extends AbstractService {

    protected function __construct() {
        $this->initializeConfig();
    }

    final public static function instance(?CLI $cli = null): CentralConfig {
        return self::setup_instance($cli);
    }

    private function initializeConfig(): void {
        $this->createConfigDir();
        $this->createInstancesConfigDir();
    }

    public function configDir(): string {
        return realpath('~/.config/mchef');
    }

    public function instancesConfigDir(): string {
        return $this->configDir().'/instances';
    }

    private function createDir(string $dir, string $onErrorMsg): void {
        try {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        } catch (\Exception $e) {
            throw new \Error($onErrorMsg.': '.$dir, 0, $e);
        }
    }

    private function createConfigDir(): void {
        $this->createDir($this->configDir(), 'Failed to create config dir');
    }

    private function createInstancesConfigDir(): void {
        $this->createDir($this->instancesConfigDir(), 'Failed to create instances config dir');
    }

    public function upsertInstanceConfig(string $instanceLocation): void {
        
    }
}
