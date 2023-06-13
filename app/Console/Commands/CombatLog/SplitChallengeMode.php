<?php

namespace App\Console\Commands\CombatLog;

use App\Service\CombatLog\CombatLogSplitServiceInterface;
use Illuminate\Console\Command;

class SplitChallengeMode extends Command
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
     *
     * @param CombatLogSplitServiceInterface $combatLogSplitService
     *
     * @return int
     */
    public function handle(CombatLogSplitServiceInterface $combatLogSplitService): int
    {
        ini_set('memory_limit', '2G');

        $filePath = $this->argument('filePath');

        return $this->parseCombatLogRecursively($filePath, function (string $filePath) use ($combatLogSplitService)
        {
            return $this->splitCombatLog($combatLogSplitService, $filePath);
        });
    }

    /**
     * @param CombatLogSplitServiceInterface $combatLogSplitService
     * @param string                         $filePath
     *
     * @return int
     */
    private function splitCombatLog(CombatLogSplitServiceInterface $combatLogSplitService, string $filePath): int
    {
        $this->info(sprintf('Parsing file %s', $filePath));
        
        $resultingFiles = $combatLogSplitService->splitCombatLogOnChallengeModes($filePath);
        foreach ($resultingFiles as $resultingFile) {
            $this->comment(sprintf('- Created file %s', $resultingFile));
        }

        // Delete the original file AFTER it was split, if nothing was done, don't do it
        if ($resultingFiles->count() > 1) {
            $this->info(sprintf('Renaming original file %s', $filePath));
            rename($filePath, str_replace('.zip', '.bak', $filePath));
        }

        return 0;
    }
}
