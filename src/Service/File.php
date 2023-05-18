<?php

namespace App\Service;

use App\Exceptions\ExecFailed;
use App\Traits\ExecTrait;
use splitbrain\phpcli\Exception;

class File extends AbstractService {
    use ExecTrait;

    final public static function instance(): File {
        return self::setup_instance();
    }

    /**
     * Copy files from point a to b.
     * @param $src
     * @param $target
     * @return string - Output from exec
     * @throws ExecFailed
     */
    public function copy_files($src, $target): string {
        $cmd = "cp -r $src/{.,}* $target";
        return $this->exec($cmd, "Failed to copy files from $src to $target: {{output}}");
    }

    private function folder_restriction_check(string $path, string $action) {
        if (!is_dir($path)) {
            throw new Exception('Invalid path '.$path);
        }
        if (realpath($path) === '/') {
            throw new Exception('You cannot '.$action.' files from root!');
        }
        if (realpath($path) === '/etc') {
            throw new Exception('You cannot '.$action.' files from etc folder!');
        }
        if (realpath($path) === '/bin') {
            throw new Exception('You cannot '.$action.' files from bin folder!');
        }
        if (realpath($path) === '/usr/bin') {
            throw new Exception('You cannot '.$action.' files from /usr/bin folder!');
        }
    }

    public function cmd_find_all_files_excluding(array $files, array $paths): string {
        $files = array_map(function($file) { return ' -not -file "'.$file.'"';}, $files );
        $paths = array_map(function($path) { return ' -not -path "'.$path.'" -not -path "'.$path.'/*"';}, $paths );
        $not = implode(' ', $files).implode(' ', $paths);
        $cmd = "find . $not";
        return $cmd;
    }

    /**
     * Delete all files
     * @param string $path - target path of which to delete files from
     * @param array $files
     * @param array $paths
     * @return string
     * @throws ExecFailed
     */
    public function delete_all_files_excluding(string $path, array $files, array $paths): string {
        $this->folder_restriction_check($path, 'delete');
        $cmd = $this->cmd_find_all_files_excluding($files, $paths);
        $cmd = "$cmd -delete";
        return $this->exec($cmd);
    }

    public function delete_dir($path) {
        if (empty($path)) {
            return false;
        }
        return is_file($path) ?
            @unlink($path) :
            array_map(function($path) {
                $this->delete_dir($path);
            }, glob($path.'/*')) == @rmdir($path);
    }

    public function temp_dir() {
        $tempDir = sys_get_temp_dir().'/'.uniqid(sha1(microtime()), true);
        mkdir($tempDir);
        return $tempDir;
    }
}
