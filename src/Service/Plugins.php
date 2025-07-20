<?php

namespace App\Service;

use App\Helpers\OS;
use App\Model\Plugin;
use App\Model\PluginsInfo;
use App\Model\Recipe;
use App\Model\RecipePlugin;
use App\Model\Volume;
use PHPUnit\Framework\MockObject\MockObject;
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Exception;
use splitbrain\phpcli\Options;

class Plugins extends AbstractService {
    final public static function instance(CLI|MockObject $cli): Plugins {
        return self::setup_instance($cli);
    }

    private function getMoodlePluginPath($pluginName): string {
        $path = '';
        $parts = explode('_', $pluginName);
        $type = array_shift($parts);

        switch ($type) {
            case 'assignfeedback':
                $path = '/mod/assign/feedback/' . implode('/', $parts);
                break;
            case 'antivirus':
                $path = '/lib/antivirus/' . implode('/', $parts);
                break;
            case 'assignsubmission':
                $path = '/mod/assign/submission/' . implode('/', $parts);
                break;
            case 'atto':
                $path = '/lib/editor/atto/plugins/' . implode('/', $parts);
                break;
            case 'auth':
                $path = '/auth/' . implode('/', $parts);
                break;
            case 'availability':
                $path = '/availability/condition/' . implode('/', $parts);
                break;
            case 'block':
                $path = '/blocks/' . implode('/', $parts);
                break;
            case 'booktool':
                $path = '/mod/book/tool/' . implode('/', $parts);
                break;
            case 'cachelock':
                $path = '/cache/locks/' . implode('/', $parts);
                break;
            case 'cachestore':
                $path = '/cache/stores/' . implode('/', $parts);
                break;
            case 'calendartype':
                $path = '/calendar/type/' . implode('/', $parts);
                break;
            case 'contenttype':
                $path = '/contentbank/contenttype/' . implode('/', $parts);
                break;
            case 'coursereport':
                $path = '/course/report/' . implode('/', $parts);
                break;
            case 'customfield':
                $path = '/customfield/field/' . implode('/', $parts);
                break;
            case 'datafield':
                $path = '/mod/data/field/' . implode('/', $parts);
                break;
            case 'dataformat':
                $path = '/dataformat/' . implode('/', $parts);
                break;
            case 'datapreset':
                $path = '/mod/data/preset/' . implode('/', $parts);
                break;
            case 'editor':
                $path = '/lib/editor/' . implode('/', $parts);
                break;
            case 'enrol':
                $path = '/enrol/' . implode('/', $parts);
                break;
            case 'fileconverter':
                $path = '/files/converter/' . implode('/', $parts);
                break;
            case 'filter':
                $path = '/filter/' . implode('/', $parts);
                break;
            case 'format':
                $path = '/course/format/' . implode('/', $parts);
                break;
            case 'forumreport':
                $path = '/mod/forum/report/' . implode('/', $parts);
                break;
            case 'gradeexport':
                $path = '/grade/export/' . implode('/', $parts);
                break;
            case 'gradeimport':
                $path = '/grade/import/' . implode('/', $parts);
                break;
            case 'gradereport':
                $path = '/grade/report/' . implode('/', $parts);
                break;
            case 'gradingform':
                $path = '/grade/grading/form/' . implode('/', $parts);
                break;
            case 'h5plib':
                $path = '/h5p/h5plib/' . implode('/', $parts);
                break;
            case 'local':
                $path = '/local/' . implode('/', $parts);
                break;
            case 'logstore':
                $path = '/admin/tool/log/store/' . implode('/', $parts);
                break;
            case 'ltiservice':
                $path = '/mod/lti/service/' . implode('/', $parts);
                break;
            case 'ltisource':
                $path = '/mod/lti/source/' . implode('/', $parts);
                break;
            case 'media':
                $path = '/media/player/' . implode('/', $parts);
                break;
            case 'message':
                $path = '/message/output/' . implode('/', $parts);
                break;
            case 'mlbackend':
                $path = '/lib/mlbackend/' . implode('/', $parts);
                break;
            case 'mnetservice':
                $path = '/mnet/service/' . implode('/', $parts);
                break;
            case 'mod':
                $path = '/mod/' . implode('/', $parts);
                break;
            case 'plagiarism':
                $path = '/plagiarism/' . implode('/', $parts);
                break;
            case 'portfolio':
                $path = '/portfolio/' . implode('/', $parts);
                break;
            case 'profilefield':
                $path = '/user/profile/field/' . implode('/', $parts);
                break;
            case 'qbank':
                $path = '/question/bank/' . implode('/', $parts);
                break;
            case 'qbehaviour':
                $path = '/question/behaviour/' . implode('/', $parts);
                break;
            case 'qformat':
                $path = '/question/format/' . implode('/', $parts);
                break;
            case 'qtype':
                $path = '/question/type/' . implode('/', $parts);
                break;
            case 'quiz':
                $path = '/mod/quiz/report/' . implode('/', $parts);
                break;
            case 'quizaccess':
                $path = '/mod/quiz/accessrule/' . implode('/', $parts);
                break;
            case 'report':
                $path = '/report/' . implode('/', $parts);
                break;
            case 'repository':
                $path = '/repository/' . implode('/', $parts);
                break;
            case 'scormreport':
                $path = '/mod/scorm/report/' . implode('/', $parts);
                break;
            case 'search':
                $path = '/search/engine/' . implode('/', $parts);
                break;
            case 'theme':
                $path = '/theme/' . implode('/', $parts);
                break;
            case 'tiny':
                $path = '/lib/editor/tiny/plugins/' . implode('/', $parts);
                break;
            case 'tinymce':
                $path = '/lib/editor/tinymce/plugins/' . implode('/', $parts);
                break;
            case 'tool':
                $path = '/admin/tool/' . implode('/', $parts);
                break;
            case 'webservice':
                $path = '/webservice/' . implode('/', $parts);
                break;
            case 'workshopallocation':
                $path = '/mod/workshop/allocation/' . implode('/', $parts);
                break;
            case 'workshopeval':
                $path = '/mod/workshop/eval/' . implode('/', $parts);
                break;
            case 'workshopform':
                $path = '/mod/workshop/form/' . implode('/', $parts);
                break;
        }
        if (empty($path)) {
            throw new Exception('Unsupported plugin: '.$pluginName);
        }
        return $path;
    }

    /**
     * Clone a git repository.
     *
     * @param string $url
     * @param string $branch
     * @param string $path
     * @param string|null $upstream
     * @throws Exception
     */
    private function cloneGithubRepository($url, $branch, $path, ?string $upstream = null) {

        if (empty($branch)) {
            $cmd = "git clone $url $path";
        } else {
            // First check if the branch exists on the remote repository
            $checkBranchCmd = "git ls-remote --heads $url $branch";
            exec($checkBranchCmd, $branchOutput, $branchReturnVar);
            
            if ($branchReturnVar != 0) {
                throw new Exception("Error checking remote repository: " . implode("\n", $branchOutput));
            }
            
            // If no output, the branch doesn't exist
            if (empty($branchOutput)) {
                throw new Exception("Branch '$branch' does not exist for repository '$url'");
            }
            
            $cmd = "git clone $url --branch $branch $path";
        }

        exec($cmd, $output, $returnVar);

        if ($returnVar != 0) {
            throw new Exception("Error cloning repository: " . implode("\n", $output));
        }

        // Add upstream remote if specified
        if (!empty($upstream)) {
            // Check if the upstream branch exists on the upstream repository
            $checkUpstreamBranchCmd = "git ls-remote --heads $upstream $branch";
            exec($checkUpstreamBranchCmd, $upstreamOutput, $upstreamReturnVar);
            
            if ($upstreamReturnVar != 0) {
                $this->cli->warning("Could not check upstream repository '$upstream': " . implode("\n", $upstreamOutput));
            } elseif (empty($upstreamOutput)) {
                $this->cli->warning("Branch '$branch' does not exist on upstream repository '$upstream'");
            } else {
                // Add upstream remote
                $addUpstreamCmd = "cd $path && git remote add upstream $upstream";
                exec($addUpstreamCmd, $upstreamAddOutput, $upstreamAddReturnVar);
                
                if ($upstreamAddReturnVar != 0) {
                    $this->cli->warning("Failed to add upstream remote: " . implode("\n", $upstreamAddOutput));
                } else {
                    $this->cli->info("Added upstream remote '$upstream' for repository");
                }
            }
        }
    }

    public function getMchefPluginsInfoPath(): string {
        $mainService = (Main::instance($this->cli));
        $chefPath = $mainService->getChefPath();
        $pluginsInfoPath = $chefPath.'/pluginsinfo.object';
        return $pluginsInfoPath;
    }

    public function loadMchefPluginsInfo(): ?PluginsInfo {
        $pluginsInfoPath = $this->getMchefPluginsInfoPath();
        if (file_exists($pluginsInfoPath)) {
            try {
                $object = unserialize(file_get_contents($pluginsInfoPath), [
                    'allowed_classes' => [PluginsInfo::class, Plugin::class, Volume::class, \stdClass::class]]);
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

    /**
     * Check if the plugins info is in sync with the recipe.
     *
     * @param Recipe $recipe
     * @param PluginsInfo $pluginsInfo
     *
     * @return bool
     */
    private function checkPluginsInfoInSync(Recipe $recipe, PluginsInfo $pluginsInfo) {
        if (empty($recipe->plugins) && empty($pluginsInfo->plugins)) {
            return true;
        }

        $pInfoRecipeSources = array_map(
            function(Plugin $plugin) {

                $recipePlugin = $this->extractRepoInfoFromPlugin($plugin->recipeSrc);

                return $recipePlugin->repo;

         }, $pluginsInfo->plugins);

        $recipePlugins = array_map(
            function($plugin) {

                $recipePlugin = $this->extractRepoInfoFromPlugin($plugin);

                return $recipePlugin->repo;

            }, $recipe->plugins);

        return empty(array_diff($pInfoRecipeSources, $recipePlugins)) && empty(array_diff($recipePlugins, $pInfoRecipeSources));
    }

    /**
     * Get plugins info from recipe.
     *
     * @param Recipe $recipe
     *
     * @return PluginsInfo|null
     */
    public function getPluginsInfoFromRecipe(Recipe $recipe): ?PluginsInfo {
        $mcPluginsInfo = $this->loadMchefPluginsInfo();
        if ($mcPluginsInfo && $this->checkPluginsInfoInSync($recipe, $mcPluginsInfo)) {
            // @TODO - we need a cli argument to prevent caching - e.g. --no-cache
            // if no-cache is passed we need it to skip this code
            $this->cli->info('Using cached plugins info');
            return $mcPluginsInfo;
        }

        if (empty($recipe->plugins)) {
            return null;
        }
        $volumes = [];
        $plugins = [];
        foreach ($recipe->plugins as $plugin) {

            $recipePlugin = $this->extractRepoInfoFromPlugin($plugin);

            // Only support single github hosted plugins for now.
            if (strpos($recipePlugin->repo, 'https://github.com') === 0 || strpos($recipePlugin->repo, 'git@github.com') === 0) {
                if ($recipe->cloneRepoPlugins) {
                    $tmpDir = sys_get_temp_dir().'/'.uniqid('', true);

                    $this->cloneGithubRepository($recipePlugin->repo, $recipePlugin->branch, $tmpDir, $recipePlugin->upstream);
                    $versionFiles = $this->findMoodleVersionFiles($tmpDir);
                    if (count($versionFiles) === 1) {
                        // Repository is a single plugin.
                        if (file_exists(OS::path($tmpDir.'/version.php'))) {
                            $pluginName = $this->getPluginComponentFromVersionFile($tmpDir.'/version.php');
                            $pluginPath = $this->getMoodlePluginPath($pluginName);
                            $ds = DIRECTORY_SEPARATOR;
                            // Strip out accidental double dir separators from path.
                            // TODO - suspect possible bug here - shouldnt be getcwd, should be project dir
                            // will be fine so long as you run mchef in project root.
                            $targetPath = str_replace("{$ds}{$ds}", $ds, OS::path(getcwd().$ds.$pluginPath));
                            if (!file_exists(OS::path($targetPath.'/version.php'))) {
                                $this->cli->info('Moving plugin from temp folder to ' . $targetPath);
                                if (!file_exists($targetPath) && !OS::isWindows()) {
                                    mkdir($targetPath, 0755, true);
                                }
                                rename($tmpDir, $targetPath);
                            } else {
                                $this->cli->info('Skipping copying '.$pluginName.' as already present at '.$targetPath);
                                // Plugin already present locally.
                                File::instance()->deleteDir($tmpDir);
                            }
                            $volumeHostPath = $targetPath;
                            if (OS::isWindows()) {
                                // Volume path for windows needs to use forward slashes to work in docker compose!
                                $volumeHostPath = Docker::instance($this->cli)->windowsToDockerPath($targetPath);
                            }
                            $volume = new Volume(...['path' => $pluginPath, 'hostPath' => $volumeHostPath]);

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

                        $this->cli->info('The Moodle plugin version information is not found in the repo.');

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
                $chefPath = realpath(dirname($recipe->getRecipePath())).'/.mchef';
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

    /**
     * Extract repo info from plugin.
     *
     * @param mixed $plugin
     *
     * @return RecipePlugin
     */
    private function extractRepoInfoFromPlugin(mixed $plugin): RecipePlugin {

        if (is_object($plugin) && empty($plugin->repo)) {
            throw new Exception('Repo not set in plugin recipe');
        }

        if (is_object($plugin)) {
            return new RecipePlugin(
                repo: $plugin->repo,
                branch: $plugin->branch ?? 'master',
                upstream: $plugin->upstream ?? null
            );
        }

        // Handle string format: "url" or "url~branch"
        if (str_contains($plugin, '~')) {
            [$repo, $branch] = explode('~', $plugin, 2);
            return new RecipePlugin(repo: $repo, branch: $branch);
        }

        return new RecipePlugin(repo: $plugin);
    }

}
