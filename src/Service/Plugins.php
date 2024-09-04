<?php

namespace App\Service;

use App\Model\Plugin;
use App\Model\PluginsInfo;
use App\Model\Recipe;
use App\Model\Volume;
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Exception;
use splitbrain\phpcli\Options;

class Plugins extends AbstractService {
    final public static function instance(CLI $cli): Plugins {
        return self::setup_instance($cli);
    }

    private function getMoodlePluginPath($pluginName): string {
        $path = '';
        $parts = explode('_', $pluginName);
        $type = array_shift($parts);

        switch ($type) {
            case 'assignfeedback':
                $path = ''.DIRECTORY_SEPARATOR.'mod'.DIRECTORY_SEPARATOR.'assign'.DIRECTORY_SEPARATOR.'feedback'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'antivirus':
                $path = ''.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'antivirus'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'assignsubmission':
                $path = ''.DIRECTORY_SEPARATOR.'mod'.DIRECTORY_SEPARATOR.'assign'.DIRECTORY_SEPARATOR.'submission'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'atto':
                $path = ''.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'editor'.DIRECTORY_SEPARATOR.'atto'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'auth':
                $path = ''.DIRECTORY_SEPARATOR.'auth'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'availability':
                $path = ''.DIRECTORY_SEPARATOR.'availability'.DIRECTORY_SEPARATOR.'condition'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'block':
                $path = ''.DIRECTORY_SEPARATOR.'blocks'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'booktool':
                $path = ''.DIRECTORY_SEPARATOR.'mod'.DIRECTORY_SEPARATOR.'book'.DIRECTORY_SEPARATOR.'tool'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'cachelock':
                $path = ''.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'locks'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'cachestore':
                $path = ''.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'stores'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'calendartype':
                $path = ''.DIRECTORY_SEPARATOR.'calendar'.DIRECTORY_SEPARATOR.'type'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'contenttype':
                $path = ''.DIRECTORY_SEPARATOR.'contentbank'.DIRECTORY_SEPARATOR.'contenttype'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'coursereport':
                $path = ''.DIRECTORY_SEPARATOR.'course'.DIRECTORY_SEPARATOR.'report'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'customfield':
                $path = ''.DIRECTORY_SEPARATOR.'customfield'.DIRECTORY_SEPARATOR.'field'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'datafield':
                $path = ''.DIRECTORY_SEPARATOR.'mod'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'field'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'dataformat':
                $path = ''.DIRECTORY_SEPARATOR.'dataformat'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'datapreset':
                $path = ''.DIRECTORY_SEPARATOR.'mod'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'preset'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'editor':
                $path = ''.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'editor'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'enrol':
                $path = ''.DIRECTORY_SEPARATOR.'enrol'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'fileconverter':
                $path = ''.DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.'converter'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'filter':
                $path = ''.DIRECTORY_SEPARATOR.'filter'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'format':
                $path = ''.DIRECTORY_SEPARATOR.'course'.DIRECTORY_SEPARATOR.'format'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'forumreport':
                $path = ''.DIRECTORY_SEPARATOR.'mod'.DIRECTORY_SEPARATOR.'forum'.DIRECTORY_SEPARATOR.'report'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'gradeexport':
                $path = ''.DIRECTORY_SEPARATOR.'grade'.DIRECTORY_SEPARATOR.'export'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'gradeimport':
                $path = ''.DIRECTORY_SEPARATOR.'grade'.DIRECTORY_SEPARATOR.'import'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'gradereport':
                $path = ''.DIRECTORY_SEPARATOR.'grade'.DIRECTORY_SEPARATOR.'report'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'gradingform':
                $path = ''.DIRECTORY_SEPARATOR.'grade'.DIRECTORY_SEPARATOR.'grading'.DIRECTORY_SEPARATOR.'form'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'h5plib':
                $path = ''.DIRECTORY_SEPARATOR.'h5p'.DIRECTORY_SEPARATOR.'h5plib'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'local':
                $path = ''.DIRECTORY_SEPARATOR.'local'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'logstore':
                $path = ''.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'tool'.DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR.'store'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'ltiservice':
                $path = ''.DIRECTORY_SEPARATOR.'mod'.DIRECTORY_SEPARATOR.'lti'.DIRECTORY_SEPARATOR.'service'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'ltisource':
                $path = ''.DIRECTORY_SEPARATOR.'mod'.DIRECTORY_SEPARATOR.'lti'.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'media':
                $path = ''.DIRECTORY_SEPARATOR.'media'.DIRECTORY_SEPARATOR.'player'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'message':
                $path = ''.DIRECTORY_SEPARATOR.'message'.DIRECTORY_SEPARATOR.'output'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'mlbackend':
                $path = ''.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'mlbackend'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'mnetservice':
                $path = ''.DIRECTORY_SEPARATOR.'mnet'.DIRECTORY_SEPARATOR.'service'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'mod':
                $path = ''.DIRECTORY_SEPARATOR.'mod'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'plagiarism':
                $path = ''.DIRECTORY_SEPARATOR.'plagiarism'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'portfolio':
                $path = ''.DIRECTORY_SEPARATOR.'portfolio'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'profilefield':
                $path = ''.DIRECTORY_SEPARATOR.'user'.DIRECTORY_SEPARATOR.'profile'.DIRECTORY_SEPARATOR.'field'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'qbank':
                $path = ''.DIRECTORY_SEPARATOR.'question'.DIRECTORY_SEPARATOR.'bank'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'qbehaviour':
                $path = ''.DIRECTORY_SEPARATOR.'question'.DIRECTORY_SEPARATOR.'behaviour'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'qformat':
                $path = ''.DIRECTORY_SEPARATOR.'question'.DIRECTORY_SEPARATOR.'format'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'qtype':
                $path = ''.DIRECTORY_SEPARATOR.'question'.DIRECTORY_SEPARATOR.'type'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'quiz':
                $path = ''.DIRECTORY_SEPARATOR.'mod'.DIRECTORY_SEPARATOR.'quiz'.DIRECTORY_SEPARATOR.'report'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'quizaccess':
                $path = ''.DIRECTORY_SEPARATOR.'mod'.DIRECTORY_SEPARATOR.'quiz'.DIRECTORY_SEPARATOR.'accessrule'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'report':
                $path = ''.DIRECTORY_SEPARATOR.'report'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'repository':
                $path = ''.DIRECTORY_SEPARATOR.'repository'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'scormreport':
                $path = ''.DIRECTORY_SEPARATOR.'mod'.DIRECTORY_SEPARATOR.'scorm'.DIRECTORY_SEPARATOR.'report'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'search':
                $path = ''.DIRECTORY_SEPARATOR.'search'.DIRECTORY_SEPARATOR.'engine'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'theme':
                $path = ''.DIRECTORY_SEPARATOR.'theme'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'tiny':
                $path = ''.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'editor'.DIRECTORY_SEPARATOR.'tiny'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'tinymce':
                $path = ''.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'editor'.DIRECTORY_SEPARATOR.'tinymce'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'tool':
                $path = ''.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'tool'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'webservice':
                $path = ''.DIRECTORY_SEPARATOR.'webservice'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'workshopallocation':
                $path = ''.DIRECTORY_SEPARATOR.'mod'.DIRECTORY_SEPARATOR.'workshop'.DIRECTORY_SEPARATOR.'allocation'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'workshopeval':
                $path = ''.DIRECTORY_SEPARATOR.'mod'.DIRECTORY_SEPARATOR.'workshop'.DIRECTORY_SEPARATOR.'eval'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
            case 'workshopform':
                $path = ''.DIRECTORY_SEPARATOR.'mod'.DIRECTORY_SEPARATOR.'workshop'.DIRECTORY_SEPARATOR.'form'.DIRECTORY_SEPARATOR.'' . implode(''.DIRECTORY_SEPARATOR.'', $parts);
                break;
        }
        if (empty($path)) {
            throw new Exception('Unsupported plugin: '.$pluginName);
        }
        return $path;
    }

    private function cloneGithubRepository($url, $path) {
        $cmd = "git clone $url $path";
        exec($cmd, $output, $returnVar);

        if ($returnVar != 0) {
            throw new Exception("Error cloning repository: " . implode("\n", $output));
        }
    }

    public function getMchefPluginsInfoPath(): string {
        $mainService = (Main::instance($this->cli));
        $chefPath = $mainService->getChefPath();
        $pluginsInfoPath = $chefPath.''.DIRECTORY_SEPARATOR.'pluginsinfo.object';
        return $pluginsInfoPath;
    }

    public function loadMchefPluginsInfo(): ?PluginsInfo {
        $pluginsInfoPath = $this->getMchefPluginsInfoPath();
        if (file_exists($pluginsInfoPath)) {
            try {
                $object = unserialize(file_get_contents($pluginsInfoPath), [
                    'allowed_classes' => [PluginsInfo::class, Plugin::class, Volume::class]]);
            } catch (\Exception) {
                return null;
            }
            if (empty($object)) {
                return null;
            }
            return $object;
        }
        return null;
    }

    private function checkPluginsInfoInSync(Recipe $recipe, PluginsInfo $pluginsInfo) {
        if (empty($recipe->plugins) && empty($pluginsInfo->plugins)) {
            return true;
        }
        $pInfoRecipeSources = array_map(
            function(Plugin $plugin) { return $plugin->recipeSrc; }, $pluginsInfo->plugins);
        $recipePlugins = array_values($recipe->plugins);
        return empty(array_diff($pInfoRecipeSources, $recipePlugins)) && empty(array_diff($recipePlugins, $pInfoRecipeSources));
    }

    public function getPluginsInfoFromRecipe(Recipe $recipe): ?PluginsInfo {
        $mcPluginsInfo = $this->loadMchefPluginsInfo();
        if ($mcPluginsInfo && $this->checkPluginsInfoInSync($recipe, $mcPluginsInfo)) {
            $this->cli->info('Using cached plugins info');
            return $mcPluginsInfo;
        }

        if (empty($recipe->plugins)) {
            return null;
        }
        $volumes = [];
        $plugins = [];
        foreach ($recipe->plugins as $plugin) {
            // Only support single github hosted plugins for now.
            if (strpos($plugin, 'https://github.com') === 0) {
                if ($recipe->cloneRepoPlugins) {
                    $tmpDir = sys_get_temp_dir().''.DIRECTORY_SEPARATOR.''.uniqid('', true);

                    $this->cloneGithubRepository($plugin, $tmpDir);
                    $versionFiles = $this->findMoodleVersionFiles($tmpDir);
                    if (count($versionFiles) === 1) {
                        // Repository is a single plugin.
                        if (file_exists($tmpDir.''.DIRECTORY_SEPARATOR.'version.php')) {
                            $pluginName = $this->getPluginComponentFromVersionFile($tmpDir.''.DIRECTORY_SEPARATOR.'version.php');
                            $pluginPath = $this->getMoodlePluginPath($pluginName);
                            $targetPath = str_replace('//', ''.DIRECTORY_SEPARATOR.'', getcwd().''.DIRECTORY_SEPARATOR.''.$pluginPath);
                            if (!file_exists($targetPath.''.DIRECTORY_SEPARATOR.'version.php')) {
                                $this->cli->info('Moving plugin from temp folder to ' . $targetPath);
                                if (!file_exists($targetPath)) {
                                    mkdir($targetPath, 0755, true);
                                }
                                rename($tmpDir, $targetPath);
                            } else {
                                $this->cli->info('Skipping copying '.$pluginName.' as already present at '.$targetPath);
                                // Plugin already present locally.
                                File::instance()->deleteDir($tmpDir);
                            }
                            $volume = new Volume(...['path' => $pluginPath, 'hostPath' => $targetPath]);
                            $volumes[] = $volume;
                            $plugins[$pluginName] = new Plugin(
                                $pluginName,
                                $pluginPath,
                                $targetPath,
                                $volume,
                                $plugin
                            );
                        }
                    } else {
                        // TODO - support plugins already in a structure.
                        throw new Exception('Unhandled case');
                    }
                }
            }
        }
        // Cache to file.
        $mainService = (Main::instance($this->cli));
        $chefPath = $mainService->getChefPath();
        if ($chefPath === null) {
            // No chef path, create one if we are at same level as recipe.
            if (realpath(dirname($recipe->getRecipePath())) === realpath(getcwd())) {
                $chefPath = realpath(dirname($recipe->getRecipePath())).''.DIRECTORY_SEPARATOR.'.mchef';
            }
        }
        if (!file_exists($chefPath)) {
            mkdir($chefPath, 0755);
        }
        $pluginsInfoPath = $this->getMchefPluginsInfoPath();
        $pluginsInfo = new PluginsInfo($volumes, $plugins);
        file_put_contents($pluginsInfoPath, serialize($pluginsInfo));
        return $pluginsInfo;
    }

    public function getPluginComponentFromVersionFile($filepath): string {
        // Read the contents of the version.php file
        $contents = file_get_contents($filepath);

        // Search for the plugin name using a regular expression
        preg_match('/\$plugin->component\s*=\s*[\'"](.+?)[\'"];/s', $contents, $matches);
        if (isset($matches[1])) {
            $pluginName = $matches[1];
        } else {
            throw new Exception('Bad plugin - does not have component in version.php');
        }

        return $pluginName;
    }

    /**
     * @param Options $options
     * @return Plugin[]
     */
    public function getPluginsCsvFromOptions(Options $options): ?array {
        $mainService = (Main::instance($this->cli));
        $recipe = $mainService->getRecipe();
        $pluginInfo = $this->getPluginsInfoFromRecipe($recipe);
        $pluginsCsv = $options->getOpt('plugins');
        if (!empty($pluginsCsv)) {
            $pluginComponentNames = array_map('trim', explode(',', $pluginsCsv));
            $pluginService = (Plugins::instance($this->cli));
            $pluginService->validatePluginComponentNames($pluginComponentNames, $pluginInfo);
            return $pluginService->getPluginsByComponentNames($pluginComponentNames, $pluginInfo);
        } else if ($pluginsCsv === '') {
            return null;
        }
        return $pluginInfo->plugins;
    }

    public function findMoodleVersionFiles($dir) {
        $versionFiles = array();

        $dirIterator = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $entry) {
            if (!$entry->isFile()) {
                continue;
            }

            if ($entry->getFilename() == 'version.php') {
                $versionFiles[] = $entry->getPathname();
            }
        }

        return $versionFiles;
    }
    
    public function getPluginByComponentName(string $pluginName, PluginsInfo $pluginsInfo): ?Plugin {
        $plugins = array_filter($pluginsInfo->plugins, function(Plugin $plugin) use ($pluginName) {
            return $pluginName === $plugin->component;
        });
        if (count($plugins) === 1) {
            return reset($plugins);
        } else if (count($plugins) > 1) {
            throw new Exception('Found more than one plugin entry for component "'.$pluginName.'" this should not happen');
        }
        return null;
    }

    /**
     * Validate plugin component names.
     * @param string[] $pluginComponents
     * @param PluginsInfo $pluginsInfo
     */
    public function validatePluginComponentNames(array $pluginComponents, PluginsInfo $pluginsInfo) {
        foreach ($pluginComponents as $pluginName) {
            $plugin = $this->getPluginByComponentName($pluginName, $pluginsInfo);
            if (!$plugin) {
                throw new Exception('Invalid plugin component name '.$pluginName);
            }
        }
    }

    /**
     * Get plugins by component names.
     * @param string[] $pluginComponents
     * @return Plugin[]
     */
    public function getPluginsByComponentNames(array $pluginComponents, PluginsInfo $pluginsInfo): array {
        $plugins = [];
        foreach ($pluginComponents as $pluginName) {
            $plugin = $this->getPluginByComponentName($pluginName, $pluginsInfo);
            $plugins[$pluginName] = $plugin;
        }
        return $plugins;
    }
}
