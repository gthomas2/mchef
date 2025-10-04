<?php

namespace App\Service;

use splitbrain\phpcli\Exception;

class Project extends AbstractService {

    // Service dependencies.
    private Main $mainService;
    private Configurator $configuratorService;
    private Plugins $pluginsService;
    private File $fileService;

    final public static function instance(): Project {
        return self::setup_singleton();
    }

    public function purgeProjectFolderOfNonPluginCode(string $instanceName) {

        $instance = $this->configuratorService->getRegisteredInstance($instanceName);
        if (!$instance) {
            throw new Exception ('Invalid instance '.$instance);
        }
        $this->recipe = $this->mainService->getRecipe($instance->recipePath);
        $projectDir = dirname($instance->recipePath);
        $recipe = $this->mainService->getRecipe();

        $pluginsInfo = $this->pluginsService->getPluginsInfoFromRecipe($recipe);
        // Get array of relative paths for plugins.
        $paths = array_map(function($volume) { return '.'.$volume->path; }, $pluginsInfo->volumes);
        // Add other paths to not delete.
        $paths[] = '.mchef'; // Definitely do not want to delete this! (TODO- this is probably unnecessary due to line below).
        $paths[] = './.*'; // Any other hidden folders at the root of this mchef dir.
        $paths[] = './_behat_dump';
        $paths[] = './*recipe.json';
        $this->cli->promptYesNo('All non project related files will be removed from this dir. Continue?', null,
            function() { die('Aborted!'); });
        $this->fileService->deleteAllFilesExcluding($projectDir, [], $paths);
    }
}
