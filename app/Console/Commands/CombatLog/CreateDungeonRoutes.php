<?php

namespace App\Console\Commands\CombatLog;

use App\Service\CombatLog\ResultEventDungeonRouteServiceInterface;

class CreateDungeonRoutes extends BaseCombatLogCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:createdungeonroutes {filePath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates dungeon routes for all _events.txt files found in the given directory.';

    /**
     * Execute the console command.
     */
    public function handle(ResultEventDungeonRouteServiceInterface $combatLogDungeonRouteService): int
    {
        $filePath = $this->argument('filePath');

        return $this->parseCombatLogRecursively($filePath, fn(string $filePath) => $this->createDungeonRouteFromCombatLog($combatLogDungeonRouteService, $filePath));
    }

    private function createDungeonRouteFromCombatLog(ResultEventDungeonRouteServiceInterface $combatLogDungeonRouteService, string $filePath): int
    {
        $this->info(sprintf('Parsing file %s', $filePath));

        if (!str_contains($filePath, '_events.txt')) {
            $this->comment(sprintf('- Skipping non-events file %s', $filePath));

            return 0;
        }

        $dungeonRoutes = $combatLogDungeonRouteService->convertCombatLogToDungeonRoutes($filePath);
        foreach ($dungeonRoutes as $dungeonRoute) {
            $this->info(
                sprintf(
                    '- Created dungeon route %s (%s, %d/%d)',
                    $dungeonRoute->public_key, __($dungeonRoute->dungeon->name),
                    $dungeonRoute->enemy_forces,
                    $dungeonRoute->mappingVersion->enemy_forces_required
                )
            );
        }

        return 0;
    }
}
