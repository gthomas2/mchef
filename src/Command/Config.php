<?php

namespace App\Command;

use App\Model\Recipe;
use App\Service\Configurator;
use App\Service\Docker;
use App\Service\File;
use App\Service\Main;
use App\Service\Plugins;
use App\Service\Project;
use App\Traits\ExecTrait;
use App\Traits\SingletonTrait;
use splitbrain\phpcli\Options;
use App\MChefCLI;

class Config extends AbstractCommand {

    use SingletonTrait;
    use ExecTrait;

    const COMMAND_NAME = 'config';

    final public static function instance(MChefCLI $cli): Config {
        $instance = self::setup_singleton($cli);
        return $instance;
    }

    private function validateLang(string $langCode) {
        $moodleLangCodes = [
            'af', 'am', 'ar', 'az', 'be', 'bg', 'bn', 'bs', 'ca', 'cs', 'cy', 'da', 'de',
            'el', 'en', 'en_ar', 'en_au', 'en_ca', 'en_nz', 'en_us', 'en_ww', 'eo', 'es',
            'es_ar', 'es_co', 'es_cr', 'es_mx', 'es_pe', 'es_ve', 'et', 'eu', 'fa', 'fi',
            'fo', 'fr', 'fr_ca', 'ga', 'gd', 'gl', 'gu', 'he', 'hi', 'hr', 'hu', 'hy',
            'id', 'is', 'it', 'ja', 'jv', 'ka', 'kk', 'km', 'kn', 'ko', 'ku', 'ky', 'la',
            'lt', 'lv', 'mk', 'ml', 'mn', 'mr', 'ms', 'mt', 'my', 'nb', 'ne', 'nl', 'nn',
            'no', 'oc', 'or', 'pa', 'pl', 'ps', 'pt', 'pt_br', 'qu', 'ro', 'ru', 'rw',
            'si', 'sk', 'sl', 'so', 'sq', 'sr', 'sr_lt', 'sv', 'sw', 'ta', 'te', 'th',
            'tl', 'tr', 'ug', 'uk', 'ur', 'uz', 'vi', 'wo', 'zh_cn', 'zh_hk', 'zh_tw', 'zu'
        ];
        if (!in_array($langCode, $moodleLangCodes)) {
            $this->cli->warning('Potentially invalid lang code selected. Lang codes should be two char or [xx_xx] for sub lang');
        }
    }

    private function setLang(string $langCode) {
        $this->validateLang($langCode);
        Configurator::instance($this->cli)->setMainConfigField('lang', $langCode);
        $this->cli->notice("Default language code has been set. Note - will only affect new installs");
    }

    private function setPassword(string $password) {
        Configurator::instance($this->cli)->setMainConfigField('adminPassword', $password);
        $this->cli->notice("Default admin password has been set. Note - will only affect new installs");
    }

    private function setProxy(bool $proxy) {
        Configurator::instance($this->cli)->setMainConfigField('useProxy', $proxy);
        $this->cli->notice("Local reverse proxy settings changed.\n".
            "NOTE: You will need to stop all your mchef instances and re-up them to use the new settings");
    }

    public function execute(Options $options): void {
        if (!empty($options->getOpt('lang'))) {
            $this->setLang($options->getOpt('lang'));
        } else if (!empty($options->getOpt('proxy'))) {
            $this->cli->promptYesNo("Enable local reverse proxy?\n".
                "(This will make all your containers accessible on port 80.)",
                onYes: fn() => $this->setProxy(true),
                onNo: fn() => $this->setProxy(false));
        } else if (!empty($options->getOpt('password'))) {
            $password = $this->cli->promptInput('Please enter a password: ');
            $this->setPassword($password);
        } else {
            $this->cli->error('Invalid config option');
        }
    }

    public function register(Options $options): void {
        $options->registerCommand(self::COMMAND_NAME, 'Configure mchef globally');
        $options->registerOption('lang', 'Set a default language code', 'l', true, self::COMMAND_NAME);
        $options->registerOption('password', 'Set a default admin password', 'a', false, self::COMMAND_NAME);
        $options->registerOption('proxy', 'Proxy all sites so that they run on 80', 'p', false, self::COMMAND_NAME);
    }
}
