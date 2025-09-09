<?php

namespace App\Console\Commands\CombatLog;

use Illuminate\Support\Collection;

abstract class BaseSplitCombatLogCommand extends BaseCombatLogCommand
{
    /**
     * Execute the console command.
     */
    public function parseCombatLogSplitRecursively(
        string $filePath,
        callable $splitCombatLogCallable,
    ): int {
        ini_set('memory_limit', '2G');

        return $this->parseCombatLogRecursively(
            $filePath,
            fn(string $filePath) => $this->splitCombatLog($splitCombatLogCallable, $filePath)
        );
    }

    private function splitCombatLog(callable $splitCombatLogCallable, string $filePath): int
    {
        $this->info(sprintf('Parsing file %s', $filePath));

        /** @var Collection<string> $resultingFiles */
        $resultingFiles = $splitCombatLogCallable($filePath);
        foreach ($resultingFiles as $resultingFile) {
            $this->comment(sprintf('- Created file %s', $resultingFile));
        }

        // Rename the original file AFTER it was split, if nothing was done, don't do it
        $targetFileExtension = '.bak';
        if ($resultingFiles->count() === 0) {
            $targetFileExtension = '.del';
            $this->warn('- File contained no splittable parts!');
        }

        $targetFileName = str_replace([
            '.txt',
            '.zip',
        ], $targetFileExtension, $filePath);

        $this->comment(sprintf('- Renaming original file to %s', $targetFileName));
        rename($filePath, $targetFileName);

        return 0;
    }
}
