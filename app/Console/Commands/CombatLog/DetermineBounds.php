<?php

namespace App\Console\Commands\CombatLog;

use App\Models\Dungeon;
use App\Service\CombatLog\CombatLogServiceInterface;

class DetermineBounds extends BaseCombatLogCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:determinebounds {filePath} {dungeon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finds the min and max coordinates of all combat log events. Useful for determining the size of the map if no coordinates are known.';

    /**
     * Execute the console command.
     */
    public function handle(CombatLogServiceInterface $combatLogService): int
    {
        ini_set('memory_limit', '2G');

        $filePath = $this->argument('filePath');
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::where('key', $this->argument('dungeon'))->firstOrFail();

        return $this->parseCombatLogRecursively(
            $filePath,
            fn(string $filePath) => $this->extractUiMapIds($combatLogService, $filePath, $dungeon),
        );
    }

    private function extractUiMapIds(
        CombatLogServiceInterface $combatLogService,
        string                    $filePath,
        Dungeon                   $dungeon,
    ): int {
        $mapBounds = $combatLogService->getBoundsFromEvents($filePath, $dungeon);

        $this->info(
            sprintf(
                'minX: %.02f, minY: %.02f, maxX: %.02f, maxY: %.02f',
                $mapBounds->getMinIngameX(),
                $mapBounds->getMinIngameY(),
                $mapBounds->getMaxIngameX(),
                $mapBounds->getMaxIngameY(),
            ),
        );

        $this->info(
            sprintf(
                'Copy me: %.02f, %.02f, %.02f, %.02f',
                $mapBounds->getMinIngameX(),
                $mapBounds->getMinIngameY(),
                $mapBounds->getMaxIngameX(),
                $mapBounds->getMaxIngameY(),
            ),
        );

        return 0;
    }
}
