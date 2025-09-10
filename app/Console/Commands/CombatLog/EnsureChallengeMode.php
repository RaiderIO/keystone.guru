<?php

namespace App\Console\Commands\CombatLog;

use App\Service\CombatLog\CombatLogServiceInterface;

class EnsureChallengeMode extends BaseCombatLogCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:ensurechallengemode {filePath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensures that a filepath contains a challenge mode. Otherwise, DELETES the file.';

    /**
     * Execute the console command.
     */
    public function handle(CombatLogServiceInterface $combatLogService): int
    {
        ini_set('memory_limit', '2G');

        $filePath = $this->argument('filePath');

        // Assume error
        return $this->parseCombatLogRecursively($filePath, fn(
            string $filePath,
        ) => $this->analyzeCombatLog($combatLogService, $filePath));
    }

    private function analyzeCombatLog(CombatLogServiceInterface $combatLogService, string $filePath): int
    {
        $this->comment(sprintf('- Analyzing %s', $filePath));

        if (($challengeModes = $combatLogService->getChallengeModes($filePath))->isEmpty()) {
            $this->info('Does NOT contain challenge modes!');
            $this->removeFile($filePath);
        } else {
            $this->info(sprintf('Contains %d challenge modes. Keeping file.', $challengeModes->count()));
        }

        return 0;
    }
}
