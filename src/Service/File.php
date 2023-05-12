<?php

namespace App\Service;

use splitbrain\phpcli\Exception;

class File extends AbstractService {
    final public static function instance(): File {
        return self::setup_instance();
    }

    public function copy_files($src, $target) {
        $cmd = "cp -r $src/{.,}* $target";
        exec($cmd, $output, $returnVar);

        if ($returnVar != 0) {
            throw new Exception("Failed to copy files from $src to $target: " . implode("\n", $output));
        }
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
}
