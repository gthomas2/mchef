<?php declare(strict_types=1);

use App\Model\Recipe;
use PHPUnit\Framework\TestCase;
use App\Service\Plugins;
use \App\Traits\CallRestrictedMethodTrait;

final class PluginsServiceTest extends TestCase {
    use CallRestrictedMethodTrait;

    public function testGetMoodlePluginPath(): void {
        $mockCli = $this->createMock(splitbrain\phpcli\CLI::class);
        $pluginsService = Plugins::instance($mockCli);
        $path = $this->callRestricted($pluginsService, 'getMoodlePluginPath', ['local_test']);
        $this->assertEquals('/local/test', $path);
    }

    //public function testGetPluginsInfoFromRecipe(): void {
    //    $mockCli = $this->createMock(splitbrain\phpcli\CLI::class);
    //    $pluginsService = Plugins::instance($mockCli);
    //    $recipeService = \App\Service\RecipeParser::instance();
    //    $recipe = $recipeService->parse(__DIR__.'/Fixtures/test-mrecipe.json');
    //    var_dump($pluginsService->getPluginsInfoFromRecipe($recipe));
    //
    //}

    public function testGetPluginComponentFromVersionFile(): void {
        $mockCli = $this->createMock(splitbrain\phpcli\CLI::class);
        $pluginsService = Plugins::instance($mockCli);
        $component = $pluginsService->getPluginComponentFromVersionFile(__DIR__.'/Fixtures/version-test.php');
        $this->assertEquals('mod_assign', $component);
    }
}
