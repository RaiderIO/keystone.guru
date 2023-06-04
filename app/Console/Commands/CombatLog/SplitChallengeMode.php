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
     * @param CombatLogSplitServiceInterface $combatLogSplitService
     * @return int
     */
    public function handle(CombatLogSplitServiceInterface $combatLogSplitService): int
    {
        ini_set('memory_limit', '2G');

        $filePath = $this->argument('filePath');
        // Assume error
        $result = -1;

        $resultCombatLogs = collect();

        if (is_dir($filePath)) {
            $this->info(sprintf('%s is a dir, parsing all files in the dir..', $filePath));
            $files = glob(sprintf('%s/*', $filePath));
            foreach ($files as $filePath) {
                $this->splitCombatLog($combatLogSplitService, $filePath);
            }
        } else {
            $this->splitCombatLog($combatLogSplitService, $filePath);
        }

        return $result;
    }

    /**
     * @param CombatLogSplitServiceInterface $combatLogSplitService
     * @param string $filePath
     * @return void
     */
    private function splitCombatLog(CombatLogSplitServiceInterface $combatLogSplitService, string $filePath): void
    {
        $this->info(sprintf('Parsing file %s', $filePath));

        // While have a successful result, keep parsing
        if (!is_file($filePath)) {
            $this->warn('- Is a dir - cannot parse');
            return;
        }

        $resultingFiles = $combatLogSplitService->splitCombatLogOnChallengeModes($filePath);
        foreach ($resultingFiles as $resultingFile) {
            $this->comment(sprintf('- Created file %s', $resultingFile));
        }

        // Delete the original file AFTER it was split, if nothing was done, don't do it
        if ($resultingFiles->count() > 1) {
            $this->info(sprintf('Deleting original file %s', $filePath));
            //            unlink($filePath);
        }
    }
}
