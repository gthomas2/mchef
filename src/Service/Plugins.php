<?php

namespace App\Service;

use App\Model\Recipe;
use App\Model\Volume;
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Exception;

class Plugins extends AbstractService {
    final public static function instance(CLI $cli): Plugins {
        return self::get_instance($cli);
    }

    private function get_moodle_plugin_path($pluginName): string {
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

    private function clone_github_repository($url, $path) {
        $cmd = "git clone $url $path";
        exec($cmd, $output, $returnVar);

        if ($returnVar != 0) {
            throw new Exception("Error cloning repository: " . implode("\n", $output));
        }
    }

    public function process_plugins(Recipe $recipe): array {
        $returnVols = [];
        if (empty($recipe->plugins)) {
            return $returnVols;
        }
        foreach ($recipe->plugins as $plugin) {
            if (strpos($plugin, 'https://github.com') === 0) {
                if ($recipe->cloneRepoPlugins) {
                    $tmpDir = sys_get_temp_dir().'/'.uniqid('', true);

                    $this->clone_github_repository($plugin, $tmpDir);
                    $versionFiles = $this->find_moodle_version_files($tmpDir);
                    if (count($versionFiles) === 1) {
                        // Repository is a single plugin.
                        if (file_exists($tmpDir.'/version.php')) {
                            $pluginName = $this->get_plugin_name_from_version_file($tmpDir.'/version.php');
                            $pluginPath = $this->get_moodle_plugin_path($pluginName);
                            $targetPath = str_replace('//', '/', getcwd().'/'.$pluginPath);
                            if (!file_exists($targetPath.'/version.php')) {
                                $this->cli->info('Moving plugin from temp folder to ' . $targetPath);
                                if (!file_exists($targetPath)) {
                                    mkdir($targetPath, 0755, true);
                                }
                                rename($tmpDir, $targetPath);
                            } else {
                                $this->cli->info('Skipping copying '.$pluginName.' as already present at '.$targetPath);
                                // Plugin already present locally.
                                File::instance()->delete_dir($tmpDir);
                            }
                            $returnVols[] = new Volume(['path' => $pluginPath, 'hostPath' => $targetPath]);
                        }
                    } else {
                        // TODO - support plugins already in a structure.
                        throw new Exception('Unhandled case');
                    }
                }
            }
        }
        return $returnVols;
    }

    public function get_plugin_name_from_version_file($filepath): string {
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

    public function find_moodle_version_files($dir) {
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
}
