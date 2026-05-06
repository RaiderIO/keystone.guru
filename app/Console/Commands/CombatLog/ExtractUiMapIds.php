<?php

namespace App\Console\Commands\CombatLog;

use App\Service\CombatLog\CombatLogServiceInterface;

class ExtractUiMapIds extends BaseCombatLogCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:extractuimapids {filePath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dumps all UI Map IDs that it can find in combat logs from a given file path';

    /**
     * Execute the console command.
     */
    public function handle(CombatLogServiceInterface $combatLogService): int
    {
        ini_set('memory_limit', '2G');

        $filePath = $this->argument('filePath');

        return $this->parseCombatLogRecursively($filePath, fn(
            string $filePath,
        ) => $this->extractUiMapIds($combatLogService, $filePath));
    }

    private function extractUiMapIds(CombatLogServiceInterface $combatLogService, string $filePath): int
    {
        if (($uiMapIds = $combatLogService->getUiMapIds($filePath))->isNotEmpty()) {
            foreach ($uiMapIds as $uiMapId => $floorName) {
                $this->info(sprintf('%d: %s', $uiMapId, $floorName));
            }
        }

        return 0;
    }
}
