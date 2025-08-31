<?php

use PHPUnit\Framework\TestCase;
use App\Command\ListAll;
use App\Helpers\Testing;
use App\Service\Configurator;
use App\Service\Main;
use App\Service\Docker;
use App\Service\RecipeParser;
use App\Model\RegistryInstance;
use App\MChefCLI;
use splitbrain\phpcli\Options;

class ListAllCommandTest extends TestCase {

    public function testListAllShowsSelectedInstance() {
        $cli = $this->createMock(MChefCLI::class);
        $cli->method('warning')->willReturnCallback(function($msg) { echo $msg . "\n"; });
        $cli->method('info')->willReturnCallback(function($msg) { echo $msg . "\n"; });

        $configurator = $this->createMock(Configurator::class);
        $main = $this->createMock(Main::class);
        $docker = $this->createMock(Docker::class);
        $options = $this->createMock(Options::class);

        $instance1 = new RegistryInstance('uuid1', '/path/to/recipe1.json', 'prefix1', null);
        $instance2 = new RegistryInstance('uuid2', '/path/to/recipe2.json', 'prefix2', null);
        $instances = [$instance1, $instance2];

        $configurator->method('getInstanceRegistry')->willReturn($instances);
        $configurator->method('getMainConfig')->willReturn((object)['instance' => 'prefix2']);
        $main->method('getDockerMoodleContainerName')->willReturn('prefix1-moodle', 'prefix2-moodle');
        $docker->method('checkContainerRunning')->willReturn(true, false);

        // Capture output
        ob_start();
        $listAll = ListAll::instance($cli);
        Testing::setRestrictedProperty($listAll, 'configurator', $configurator);
        Testing::setRestrictedProperty($listAll, 'main', $main);
        Testing::setRestrictedProperty($listAll, 'docker', $docker);
        $recipeParser = $this->createMock(\App\Service\RecipeParser::class);
        // Provide required constructor arguments for Recipe
        $recipeParser->method('parse')->willReturn(new \App\Model\Recipe('4.1.0', '8.0', 'dummy', '1.0'));
        Testing::setRestrictedProperty($listAll, 'recipeParser', $recipeParser);

        // Simulate execution
        $listAll->execute($options);
        $output = ob_get_clean();

        $this->assertStringContainsString("*SELECTED*", $output);
        $this->assertStringContainsString("prefix2", $output);
    }

    public function testListAllHandlesMissingRecipe() {
        $cli = $this->createMock(MChefCLI::class);
        $cli->method('warning')->willReturnCallback(function($msg) { echo $msg . "\n"; });
        $configurator = $this->createMock(Configurator::class);
        $main = $this->createMock(Main::class);
        $docker = $this->createMock(Docker::class);
        $options = $this->createMock(Options::class);

        $instance = new RegistryInstance('uuid1', '/missing/recipe.json', 'prefix1', null);
        $configurator->method('getInstanceRegistry')->willReturn([$instance]);
        $configurator->method('getMainConfig')->willReturn((object)['instance' => 'prefix1']);
        $main->method('getDockerMoodleContainerName')->willReturn('prefix1-moodle');
        $docker->method('checkContainerRunning')->willReturn(false);

        ob_start();
        $listAll = ListAll::instance($cli);
        Testing::setRestrictedProperty($listAll, 'cli', $cli);
        Testing::setRestrictedProperty($listAll, 'configurator', $configurator);
        Testing::setRestrictedProperty($listAll, 'main', $main);
        Testing::setRestrictedProperty($listAll, 'docker', $docker);
        $recipeParser = $this->createMock(\App\Service\RecipeParser::class);
        $recipeParser->method('parse')->willReturn(new \App\Model\Recipe('4.1.0', '8.0', 'dummy', '1.0'));
        Testing::setRestrictedProperty($listAll, 'recipeParser', $recipeParser);

        $listAll->execute($options);
        $output = ob_get_clean();
        $this->assertStringContainsString('⚠️ Recipe missing', $output);
    }

    public function testListAllWithNoInstances() {
        $cli = $this->createMock(MChefCLI::class);
        $cli->method('info')->willReturnCallback(function($msg) { echo $msg . "\n"; });
        $configurator = $this->createMock(Configurator::class);
        $main = $this->createMock(Main::class);
        $docker = $this->createMock(Docker::class);
        $options = $this->createMock(Options::class);
        $configurator->method('getInstanceRegistry')->willReturn([]);
        $configurator->method('getMainConfig')->willReturn((object)['instance' => null]);
        $recipeParser = $this->createMock(RecipeParser::class);
        $listAll = ListAll::instance($cli);
        Testing::setRestrictedProperty($listAll, 'configurator', $configurator);
        Testing::setRestrictedProperty($listAll, 'main', $main);
        Testing::setRestrictedProperty($listAll, 'docker', $docker);
        Testing::setRestrictedProperty($listAll, 'recipeParser', $recipeParser);
        ob_start();
        $listAll->execute($options);
        $output = ob_get_clean();
        $this->assertEmpty(trim($output));
    }

    public function testListAllWithAllInactiveInstances() {
        $cli = $this->createMock(MChefCLI::class);
        $cli->method('info')->willReturnCallback(function($msg) { echo $msg . "\n"; });
        $configurator = $this->createMock(Configurator::class);
        $main = $this->createMock(Main::class);
        $docker = $this->createMock(Docker::class);
        $options = $this->createMock(Options::class);
        $instance1 = new RegistryInstance('uuid1', '/path/to/recipe1.json', 'prefix1', null);
        $instance2 = new RegistryInstance('uuid2', '/path/to/recipe2.json', 'prefix2', null);
        $instances = [$instance1, $instance2];
        $configurator->method('getInstanceRegistry')->willReturn($instances);
        $configurator->method('getMainConfig')->willReturn((object)['instance' => null]);
        $main->method('getDockerMoodleContainerName')->willReturn('prefix1-moodle', 'prefix2-moodle');
        $docker->method('checkContainerRunning')->willReturn(false, false);
        $recipeParser = $this->createMock(RecipeParser::class);
        $recipeParser->method('parse')->willReturn(new \App\Model\Recipe('4.1.0', '8.0', 'dummy', '1.0'));
        $listAll = ListAll::instance($cli);
        Testing::setRestrictedProperty($listAll, 'configurator', $configurator);
        Testing::setRestrictedProperty($listAll, 'main', $main);
        Testing::setRestrictedProperty($listAll, 'docker', $docker);
        Testing::setRestrictedProperty($listAll, 'recipeParser', $recipeParser);
        ob_start();
        $listAll->execute($options);
        $output = ob_get_clean();
        $this->assertStringContainsString('⏸️', $output);
        $this->assertStringNotContainsString('*SELECTED*', $output);
    }

    public function testListAllWithSelectedInactiveInstance() {
        $cli = $this->createMock(MChefCLI::class);
        $cli->method('info')->willReturnCallback(function($msg) { echo $msg . "\n"; });
        $configurator = $this->createMock(Configurator::class);
        $main = $this->createMock(Main::class);
        $docker = $this->createMock(Docker::class);
        $options = $this->createMock(Options::class);
        $instance1 = new RegistryInstance('uuid1', '/path/to/recipe1.json', 'prefix1', null);
        $configurator->method('getInstanceRegistry')->willReturn([$instance1]);
        $configurator->method('getMainConfig')->willReturn((object)['instance' => 'prefix1']);
        $main->method('getDockerMoodleContainerName')->willReturn('prefix1-moodle');
        $docker->method('checkContainerRunning')->willReturn(false);
        $recipeParser = $this->createMock(RecipeParser::class);
        $recipeParser->method('parse')->willReturn(new \App\Model\Recipe('4.1.0', '8.0', 'dummy', '1.0'));
        $listAll = ListAll::instance($cli);
        Testing::setRestrictedProperty($listAll, 'configurator', $configurator);
        Testing::setRestrictedProperty($listAll, 'main', $main);
        Testing::setRestrictedProperty($listAll, 'docker', $docker);
        Testing::setRestrictedProperty($listAll, 'recipeParser', $recipeParser);
        ob_start();
        $listAll->execute($options);
        $output = ob_get_clean();
        $this->assertStringContainsString('*SELECTED*', $output);
        $this->assertStringContainsString('⏸️', $output);
    }
}
