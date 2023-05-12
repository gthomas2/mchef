<?php

namespace App\Traits;

use App\Exceptions\ExecFailed;

trait ExecTrait {
    protected function exec(string $cmd, ?string $errorMsg = null): string {
        exec($cmd, $output, $returnVar);

        if ($returnVar != 0) {
            throw new ExecFailed($errorMsg ?? "Exec failed", 0, $cmd);
        }

        return implode("\n", $output);
    }

    protected function execStream(string $cmd, ?string $errorMsg = null): string {
        $outputBuffering = ini_get('output_buffering');
        ini_set('output_buffering', 0);
        flush();
        $output = system($cmd, $returnVar);
        if ($returnVar != 0) {
            // Restore output buffering.
            ini_set('output_buffering', $outputBuffering);
            throw new ExecFailed($errorMsg ?? "Exec failed", 0, $cmd);
        }

        // Restore output buffering.
        ini_set('output_buffering', $outputBuffering);
        return $output;
    }

    protected function execPassthru(string $cmd, ?string $errorMsg = null): void {
        $outputBuffering = ini_get('output_buffering');
        ini_set('output_buffering', 0);
        flush();
        $output = passthru($cmd, $returnVar);
        if ($returnVar != 0) {
            // Restore output buffering.
            ini_set('output_buffering', $outputBuffering);
            throw new ExecFailed($errorMsg ?? "Exec failed", 0, $cmd);
        }

        // Restore output buffering.
        ini_set('output_buffering', $outputBuffering);
    }
}
