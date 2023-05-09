<?php

namespace App\Service;

use splitbrain\phpcli\Exception;
use stdClass;
use App\Model\Recipe;

class RecipeParser extends AbstractService {
    final public static function instance(): RecipeParser {
        return self::get_instance();
    }

    public function parse(string $filePath): Recipe {
        if (!file_exists($filePath)) {
            throw new Exception('Recipe file does not exist - '.$filePath);
        }
        $contents = file_get_contents($filePath);
        $object = null;
        try {
            $object = json_decode($contents);
        } catch (\Exception $e) {
            throw new Exception('Failed to decode recipe JSON. Recipe: '.$filePath);
        }
        $this->validate($object, $filePath);

        return new Recipe($object);
    }

    protected function validate(stdClass $object, string $filePath) {
        $requiredProps = [
            'moodleTag',
            'phpVersion'
        ];

        foreach ($requiredProps as $requiredProp) {
            if (!property_exists($object, $requiredProp)) {
                throw new Exception("Missing property in recipe \"$requiredProp\" - . Recipe: $filePath");
            }
        }

        $validPHPVersions = (new PHPVersions())->listVersions();
        if (!in_array($object->phpVersion, $validPHPVersions)) {
            $supported = implode(', ', $validPHPVersions);
            throw new Exception("Unsupported php version $object->phpVersion - supported versions are $supported");
        }
    }
}