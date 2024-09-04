<?php declare(strict_types=1);

use App\Model\Recipe;
use App\Service\RecipeParser;
use PHPUnit\Framework\TestCase;
use splitbrain\phpcli\Exception;

final class RecipeParserServiceTest extends TestCase {
    
    private $validJson = '
    {
      "name": "example",
      "moodleTag": "v4.1.0",
      "phpVersion": "8.0",
      "plugins": ["https://github.com/gthomas2/moodle-moodle-filter_imageopt"],
      "containerPrefix": "imageopt",
      "host": "moodle-image-opt.test",
      "port": 80,
      "updateHostHosts": false,
      "dbType": "pgsql",
      "developer": true
    }';

    public function testParseValidRecipe(): void {
        $filePath = tempnam(sys_get_temp_dir(), 'recipe_');
        file_put_contents($filePath, $this->validJson);

        $recipeParser = RecipeParser::instance();
        $recipe = $recipeParser->parse($filePath);

        $this->assertInstanceOf(Recipe::class, $recipe);
        $this->assertEquals('v4.1.0', $recipe->moodleTag);
        $this->assertEquals('8.0', $recipe->phpVersion);
        $this->assertEquals('moodle-image-opt.test', $recipe->host);
        $this->assertEquals(80, $recipe->port);
        $this->assertEquals('pgsql', $recipe->dbType);

        unlink($filePath);
    }

    public function testParseInvalidJson(): void {
        $filePath = tempnam(sys_get_temp_dir(), 'recipe_');
        file_put_contents($filePath, 'invalid json');

        $recipeParser = RecipeParser::instance();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to decode recipe JSON');
        $recipeParser->parse($filePath);

        unlink($filePath);
    }

    public function testParseMissingFile(): void {
        $filePath = '/path/to/nonexistent/recipe.json';
        $recipeParser = RecipeParser::instance();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Recipe file does not exist');
        $recipeParser->parse($filePath);
    }

    public function testParseMissingRequiredProperty(): void {
        $invalidJson = '
        {
          "name": "example",
          "phpVersion": "8.0"
        }';
        
        $filePath = tempnam(sys_get_temp_dir(), 'recipe_');
        file_put_contents($filePath, $invalidJson);

        $recipeParser = RecipeParser::instance();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing property in recipe');
        $recipeParser->parse($filePath);

        unlink($filePath);
    }

    //public function testParseUnsupportedPhpVersion(): void {
    //    $invalidJson = '
    //    {
    //      "name": "example",
    //      "moodleTag": "v4.1.0",
    //      "phpVersion": "7.0"
    //    }';
    //    
    //    $filePath = tempnam(sys_get_temp_dir(), 'recipe_');
    //    file_put_contents($filePath, $invalidJson);
//
    //    $recipeParser = RecipeParser::instance();
//
    //    $this->expectException(Exception::class);
    //    $this->expectExceptionMessage('Unsupported php version');
    //    $recipeParser->parse($filePath);
//
    //    unlink($filePath);
    //}

    public function testSetDefaults(): void {
        $recipeParser = RecipeParser::instance();
        
        $json = '
        {
          "name": "example",
          "moodleTag": "v4.1.0",
          "phpVersion": "8.0",
          "host": "moodle-image-opt.test",
          "includeBehat": true
        }';
        
        $filePath = tempnam(sys_get_temp_dir(), 'recipe_');
        file_put_contents($filePath, $json);

        $recipe = $recipeParser->parse($filePath);

        $this->assertEquals('http://moodle-image-opt.test', $recipe->wwwRoot);
        $this->assertEquals('moodle-image-opt.test.behat', $recipe->behatHost);
        $this->assertEquals('http://moodle-image-opt.test.behat', $recipe->behatWwwRoot);
        $this->assertEquals('mc-moodle', $recipe->dbName);

        unlink($filePath);
    }
}
