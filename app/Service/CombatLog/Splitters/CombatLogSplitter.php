<?php

namespace App\Service\CombatLog\Splitters;

use App\Service\CombatLog\Splitters\Logging\CombatLogSplitterLoggingInterface;

abstract class CombatLogSplitter implements CombatLogSplitterInterface
{
    public function __construct(private readonly CombatLogSplitterLoggingInterface $log)
    {
    }

    /**
     * Based on the currently known information (as for what dungeon we're doing), generate a file path
     * to save the current combat log at.
     */
    protected function generateTargetCombatLogFileName(string $filePath): string
    {
        // Use $filePath here since it's the original location of the .txt/.zip file. We may be reading
        // the combat log ($targetFilePath) from a completely different location. We want to save the
        // new combat log in the original location instead of the location we're reading from.
        $count = 0;
        do {
            $countStr     = $count === 0 ? '' : sprintf('-%d', $count);
            $saveFilePath = sprintf(
                '%s/%s.txt',
                dirname($filePath),
                $this->getCombatLogFileName($countStr),
            );

            $this->log->generateTargetCombatLogFileNameAttempt($saveFilePath);
            // While we have a zip file that already exists, someone may have done two
            // the same dungeons of the same key level
            $count++;
        } while (file_exists(str_replace('.txt', '.zip', $saveFilePath)));

        return $saveFilePath;
    }

    protected abstract function getCombatLogFileName(string $countStr): string;
}
