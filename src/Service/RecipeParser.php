<?php

namespace App\Service;

use splitbrain\phpcli\Exception;
use stdClass;
use App\Model\Recipe;

class RecipeParser extends AbstractService {
    final public static function instance(): RecipeParser {
        return self::setup_instance();
    }

    public function parse(string $filePath): Recipe {
        if (!file_exists($filePath)) {
            throw new Exception('Recipe file does not exist - '.$filePath);
        }
        $contents = file_get_contents($filePath);
        try {
            $object = json_decode($contents);
        } catch (\Exception $e) {
            throw new Exception('Failed to decode recipe JSON. Recipe: '.$filePath, 0, $e);
        }
        if (empty($object)) {
            throw new Exception('Failed to decode recipe JSON. Recipe: '.$filePath);
        }
        $this->validate($object, $filePath);

        $recipe = new Recipe(...(array) $object);

        $this->setDefaults($recipe);
        
        return $recipe;
    }

    private function validate(stdClass $object, string $filePath) {
        $requiredProps = [
            'moodleTag',
            'phpVersion'
        ];

        foreach ($requiredProps as $requiredProp) {
            if (!property_exists($object, $requiredProp)) {
                throw new Exception("Missing property in recipe \"$requiredProp\" - . Recipe: $filePath");
            }
        }

        $validPHPVersions = (PHPVersions::instance())->listVersions();
        if (!in_array($object->phpVersion, $validPHPVersions)) {
            $supported = implode(', ', $validPHPVersions);
            throw new Exception("Unsupported php version $object->phpVersion - supported versions are $supported");
        }
    }

    private function setDefaults(Recipe $recipe) {

        // Setup port and wwwRoot.
        $recipe->port = $recipe->port ?? 80;
        $portStr = $recipe->port === 80 ? '' : ':'.$recipe->port;
        $recipe->wwwRoot = $recipe->hostProtocol.'://'.$recipe->host.($portStr);

        // Setup behat defaults.
        if ($recipe->includeBehat) {
            if (empty($recipe->behatHost)) {
                if (!empty($recipe->host)) {
                    $recipe->behatHost = $recipe->host . '.behat';
                } else {
                    throw new Exception('You must specify either a host or behatHost!');
                }
            }
            $recipe->behatWwwRoot = $recipe->hostProtocol.'://'.$recipe->behatHost.($portStr);
        }
    }
}