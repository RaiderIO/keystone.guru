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
     *
     * @param ResultEventDungeonRouteServiceInterface $combatLogDungeonRouteService
     *
     * @return int
     */
    public function handle(ResultEventDungeonRouteServiceInterface $combatLogDungeonRouteService): int
    {
        $filePath = $this->argument('filePath');

        return $this->parseCombatLogRecursively($filePath, function (string $filePath) use ($combatLogDungeonRouteService) {
            return $this->createDungeonRouteFromCombatLog($combatLogDungeonRouteService, $filePath);
        });
    }

    /**
     * @param ResultEventDungeonRouteServiceInterface $combatLogDungeonRouteService
     * @param string                                  $filePath
     *
     * @return int
     */
    private function createDungeonRouteFromCombatLog(ResultEventDungeonRouteServiceInterface $combatLogDungeonRouteService, string $filePath): int
    {
        $this->info(sprintf('Parsing file %s', $filePath));

        if (!str_contains($filePath, '_events.txt')) {
            $this->comment(sprintf('- Skipping non-events file %s', $filePath));
            return -1;
        }

        $dungeonRoutes = $combatLogDungeonRouteService->convertCombatLogToDungeonRoutes($filePath);
        foreach ($dungeonRoutes as $dungeonRoute) {
            $this->info(sprintf('- Created dungeon route %s (%s)', $dungeonRoute->public_key, __($dungeonRoute->dungeon->name)));
        }

        return 0;
    }
}
