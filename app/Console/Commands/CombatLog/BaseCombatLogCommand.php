<?php

namespace App\Console\Commands\CombatLog;

use Illuminate\Console\Command;

abstract class BaseCombatLogCommand extends Command
{
    /**
     * Parse combat logs recursively if $filePath is a folder. $callback is called for each combat log found.
     */
    protected function parseCombatLogRecursively(string $filePath, callable $callback): int
    {
        $result = -1;

        if (is_dir($filePath)) {
            $this->info(sprintf('%s is a dir, parsing all files in the dir..', $filePath));
            foreach (glob(sprintf('%s/*', $filePath)) as $filePath) {
                // While have a successful result, keep parsing
                if (!is_file($filePath)) {
                    continue;
                }

                if (!str_ends_with($filePath, '.zip') && !str_ends_with($filePath, '.txt')) {
                    $this->comment(sprintf('Skipping file %s (not a .zip or .txt)', $filePath));
                    continue;
                }

                if (($result = $callback($filePath)) !== 0) {
                    break;
                }
            }
        } else {
            $result = $callback($filePath);
        }

        return $result;
    }
}
