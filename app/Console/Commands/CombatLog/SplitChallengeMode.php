<?php

namespace App\Console\Commands\CombatLog;

use App\Service\CombatLog\CombatLogSplitServiceInterface;

class SplitChallengeMode extends BaseSplitCombatLogCommand
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
    protected $description = "Ensures that a filepath's combat logs contain just one challenge mode. If more are found, the combat log is split up.";

    /**
     * Execute the console command.
     */
    public function handle(CombatLogSplitServiceInterface $combatLogSplitService): int
    {
        ini_set('memory_limit', '2G');

        $filePath = $this->argument('filePath');

        return $this->parseCombatLogSplitRecursively($filePath, $combatLogSplitService->splitCombatLogOnChallengeModes(...));
    }
}
