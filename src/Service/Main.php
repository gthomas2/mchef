<?php

namespace App\Service;

use App\Model\DockerData;
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Exception;

class Main extends AbstractService {

    final public static function instance(CLI $cli): Main {
        return self::get_instance($cli);
    }

    private function startDocker($ymlPath) {
        $this->cli->notice('Starting docker containers');
        $cmd = "docker-compose -f $ymlPath up --force-recreate --build";
        die ($cmd);
        exec($cmd, $output, $returnVar);

        if ($returnVar != 0) {
            throw new Exception("Error starting docker containers: " . implode("\n", $output));
        }
    }

    public function create(string $recipeFilePath) {
        $this->cli->notice('Cooking up recipe '.$recipeFilePath);
        if (stripos(getcwd(), 'moodle-chef') !== false) {
            throw new Exception('You should not run mchef from within the moodle-chef folder.'.
                "\nYou should instead, create a link to mchef in your bin folder and then run it from a project folder.".
                "\n\nphp mchef.php -i will do this for you. You'll need to open a fresh terminal once it has completed.".
                "\nAt that point you should be able to call mchef.php without prefixing with the php command."
            );
        }
        $parser = (RecipeParser::instance());
        $recipe = $parser->parse($recipeFilePath);
        $this->cli->success('Recipe successfully parsed.');

        $volumes = (Plugins::instance($this->cli))->process_plugins($recipe);
        if ($volumes) {
            $this->cli->info('Volumes will be created for plugins: '.implode("\n", array_map(function($vol) {return $vol->path;}, $volumes)));
        }

        $dockerData = new DockerData($recipe);
        $dockerData->volumes = $volumes;

        $loader = new \Twig\Loader\FilesystemLoader(__DIR__.'/../../templates');
        $twig = new \Twig\Environment($loader);
        $dockerFileContents = $twig->render('main.dockerfile', (array) $dockerData);

        $recipePath = getcwd().'/.mchef/docker';
        if (!file_exists($recipePath)) {
            mkdir($recipePath, 0755, true);
        }

        $dockerData->dockerFile = $recipePath.'/Dockerfile';
        file_put_contents($dockerData->dockerFile, $dockerFileContents);

        $dockerComposeFileContents = $twig->render('main.compose.yml', (array) $dockerData);
        $ymlPath = $recipePath.'/main.compose.yml';
        file_put_contents($ymlPath, $dockerComposeFileContents);

        $this->startDocker($ymlPath);
    }
}