<?php

namespace App\Service;

use splitbrain\phpcli\Exception;
use stdClass;
use App\Model\Recipe;

class RecipeParser extends AbstractService {
    final public static function instance(): RecipeParser {
        return self::setup_singleton();
    }

    public function parse(string $filePath): Recipe {
        if (!file_exists($filePath)) {
            throw new Exception('Recipe file does not exist - '.$filePath);
        }
        $contents = file_get_contents($filePath);

        try {
            $recipe = ModelJSONDeserializer::instance()->deserialize($contents, Recipe::class);
        } catch (\Exception $e) {
            throw new Exception('Failed to decode recipe JSON. Recipe: '.$filePath, 0, $e);
        }

        // Validate required properties
        $this->validateRecipe($recipe, $filePath);

        $this->setDefaults($recipe);
        $recipe->setRecipePath($filePath);

        return $recipe;
    }

    private function validateRecipe(Recipe $recipe, string $filePath) {
        // Validate required properties - these are already validated by the constructor
        // but we can add additional business logic validation here

        $validPHPVersions = (PHPVersions::instance())->listVersions();
        if (!in_array($recipe->phpVersion, $validPHPVersions)) {
            $supported = implode(', ', $validPHPVersions);
            throw new Exception("Unsupported php version $recipe->phpVersion - supported versions are $supported");
        }
    }

    private function getPortString(Recipe $recipe): ?string {
        $recipe->port = $recipe->port ?? 80;
        return $recipe->port === 80 ? '' : ':'.$recipe->port;
    }

    public function getBehatHost(Recipe $recipe): ?string {
        if (!$recipe->includeBehat) {
            return null;
        }
        if (empty($recipe->behatHost)) {
            if (!empty($recipe->host)) {
                return $recipe->host . '.behat';
            } else {
                throw new Exception('You must specify either a host or behatHost!');
            }
        }
        return null;
    }

    private function setDefaults(Recipe $recipe) {

        // Setup port and wwwRoot.
        $recipe->port = $recipe->port ?? 80;
        $portStr = $this->getPortString($recipe);
        $recipe->wwwRoot = $recipe->hostProtocol.'://'.$recipe->host.($portStr);

        // Setup behat defaults.
        if ($recipe->includeBehat) {
            $recipe->behatHost = $this->getBehatHost($recipe);
            $recipe->behatWwwRoot = $recipe->hostProtocol.'://'.$recipe->behatHost.($portStr);
        }

        // Setup database defaults.
        if (empty($recipe->dbName)) {
            $recipe->dbName = ($recipe->containerPrefix ?? 'mc').'-moodle';
        }
    }
}
