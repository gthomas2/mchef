<?php

namespace App\Service;

use App\Exceptions\ExecFailed;
use App\Helpers\OS;
use App\Traits\ExecTrait;
use splitbrain\phpcli\Exception;

class File extends AbstractService {
    use ExecTrait;

    final public static function instance(): File {
        return self::setup_singleton();
    }

    /**
     * Copy files from point a to b.
     * @param $src
     * @param $target
     * @return string - Output from exec
     * @throws ExecFailed
     */
    public function copyFiles($src, $target): string {
        $this->folderRestrictionCheck($src, 'copy');
        $this->folderRestrictionCheck($target, 'copy');

        if (!OS::isWindows()) {
            $cmd = sprintf(
                "cp -r %s/{.,}* %s",
                OS::escShellArg($src),
                OS::escShellArg($target)
            );
        } else {
            $cmd = sprintf(
                'powershell -Command "Copy-Item -Path %s -Destination %s -Recurse -Force -ErrorAction Stop"',
                OS::escShellArg("$src\\*"),
                OS::escShellArg($target)
            );
        }

        return $this->exec($cmd, "Failed to copy files from $src to $target: {{output}}");
    }

    private function folderRestrictionCheck(string $path, string $action) {
        if (!is_dir($path)) {
            throw new Exception('Invalid path: ' . $path);
        }

        $realPath = realpath($path);
        if ($realPath === false) {
            throw new Exception('Could not resolve real path for: ' . $path);
        }

        if (!OS::isWindows()) {
            if ($realPath === DIRECTORY_SEPARATOR) {
                throw new Exception('You cannot ' . $action . ' files from root!');
            }
        } else {
            // Windows sensitive folders
            $restrictedWindowsPaths = [
                'C:\\Windows',         // Windows system folder
                'C:\\Windows\\System32',
                'C:\\Windows\\SysWOW64',
                'C:\\Program Files',   // Default installation folder
                'C:\\Program Files (x86)',
                'C:\\Users\\Administrator',  // Admin home
                'C:\\Users\\Public',  // Public shared files
                'C:\\',               // Root of C drive
            ];

            foreach ($restrictedWindowsPaths as $restrictedPath) {
                if (stripos($realPath, $restrictedPath) === 0) {
                    throw new Exception('You cannot ' . $action . ' files from sensitive Windows system directories!');
                }
            }
        }

        // Unix-sensitive folders
        $restrictedUnixPaths = [
            OS::path('/etc'),
            OS::path('/bin'),
            OS::path('/usr/bin'),
            OS::path('/var/lib'),
            OS::path('/boot'),
            OS::path('/sbin'),
        ];

        if (in_array($realPath, $restrictedUnixPaths, true)) {
            throw new Exception('You cannot ' . $action . ' files from sensitive Unix system directories!');
        }
    }


    public function cmdFindAllFilesExcluding(array $files, array $paths): string {
        if (!OS::isWindows()) {
            $files = array_map(fn($file) => ' -not -file ' . OS::escShellArg($file), $files);
            $paths = array_map(fn($path) => ' -not -path ' . OS::escShellArg($path) . ' -not -path ' . OS::escShellArg($path . DIRECTORY_SEPARATOR . '*'), $paths);
            return "find . " . implode(' ', $files) . implode(' ', $paths);
        }

        // PowerShell Alternative for Windows
        $notFiles = implode(' -and ', array_map(fn($file) => "-not (Get-Item " . OS::escShellArg($file) . ")", $files));
        $notPaths = implode(' -and ', array_map(fn($path) => "-not (Get-Item " . OS::escShellArg($path) . ") -and -not (Get-Item " . OS::escShellArg($path . '\\*') . ")", $paths));

        return sprintf(
            'powershell -Command "Get-ChildItem -Path . -Recurse | Where-Object { %s %s }"',
            $notFiles,
            $notPaths
        );
    }

    /**
     * Delete all files excluding specific files
     * @param string $path - target path of which to delete files from
     * @param array $files
     * @param array $paths
     * @return string
     * @throws ExecFailed
     */
    public function deleteAllFilesExcluding(string $path, array $files, array $paths): string {
        $this->folderRestrictionCheck($path, 'delete');

        if (!OS::isWindows()) {
            $cmd = $this->cmdFindAllFilesExcluding($files, $paths);
            $cmd = "$cmd -delete";
        } else {
            // PowerShell equivalent for Windows
            $notFiles = implode(' -and ', array_map(fn($file) => "-not (Get-Item '$file')", $files));
            $notPaths = implode(' -and ', array_map(fn($path) => "-not (Get-Item '$path') -and -not (Get-Item '$path\\*')", $paths));

            $cmd = sprintf(
                'powershell -Command "Get-ChildItem -Path %s -Recurse | Where-Object { %s %s } | Remove-Item -Force -Recurse"',
                OS::escShellArg($path),
                $notFiles,
                $notPaths
            );
        }

        return $this->exec($cmd);
    }


    public function deleteDir($path) {
        if (empty($path)) {
            return false;
        }

        if (is_file($path)) {
            return @unlink($path);
        }

        foreach (glob(OS::path("$path/*")) as $file) {
            $this->deleteDir($file);
        }

        return @rmdir($path);
    }


    public function tempDir() {
        $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid(sha1(microtime()), true);
        mkdir($tempDir);
        return $tempDir;
    }

    private function getRootDirectoryWindows($currentDir) {
        if (preg_match('/^[A-Z]:\\\\/', $currentDir, $matches)) {
            return $matches[0];
        }
        return 'C:\\'; // Fallback if something goes wrong
    }

    function findFileInOrAboveDir($filename, ?string $dir = null): ?string {
        $currentDir = $dir ?? getcwd();
        $rootDir = OS::isWindows() ? $this->getRootDirectoryWindows($currentDir) : '/';

        while ($currentDir !== $rootDir && $currentDir !== false) {
            $filePath = OS::path("$currentDir/$filename");

            if (file_exists($filePath)) {
                return $filePath;
            }

            $parentDir = realpath(OS::path($currentDir .'/..'));
            if ($parentDir === $currentDir) {
                break; // Prevent infinite loop at root
            }

            $currentDir = $parentDir;
        }

        return null;
    }

}
