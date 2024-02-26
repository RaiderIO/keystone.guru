<?php

namespace App\Console\Commands\CombatLog;

use App\Service\CombatLog\CombatLogSplitServiceInterface;

class SplitChallengeMode extends BaseCombatLogCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:splitchallengemode {filePath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensures that a filepath\'s combat logs contain just one challenge mode. If more are found, the combat log is split up.';

    /**
     * Execute the console command.
     */
    public function handle(CombatLogSplitServiceInterface $combatLogSplitService): int
    {
        ini_set('memory_limit', '2G');

        $filePath = $this->argument('filePath');

        return $this->parseCombatLogRecursively($filePath, fn (string $filePath) => $this->splitCombatLog($combatLogSplitService, $filePath));
    }

    private function splitCombatLog(CombatLogSplitServiceInterface $combatLogSplitService, string $filePath): int
    {
        $this->info(sprintf('Parsing file %s', $filePath));

        $resultingFiles = $combatLogSplitService->splitCombatLogOnChallengeModes($filePath);
        foreach ($resultingFiles as $resultingFile) {
            $this->comment(sprintf('- Created file %s', $resultingFile));
        }

        // Rename the original file AFTER it was split, if nothing was done, don't do it
        $targetFileExtension = '.bak';
        if ($resultingFiles->count() === 0) {
            $targetFileExtension = '.del';
            $this->warn('- File contained no challenge modes!');
        }

        $targetFileName = str_replace(['.txt', '.zip'], $targetFileExtension, $filePath);

        $this->comment(sprintf('- Renaming original file to %s', $targetFileName));
        rename($filePath, $targetFileName);

        return 0;
    }
}
