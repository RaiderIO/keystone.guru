<?php

namespace App\Console\Commands\CombatLog;

use App\Service\CombatLog\CombatLogDungeonRouteServiceInterface;
use Illuminate\Console\Command;

class CreateDungeonRoutes extends Command
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
    protected $description = 'Ensures that a filepath\'s combat logs contain just one challenge mode. If more are found, the combat log is split up.';

    /**
     * Execute the console command.
     *
     * @param CombatLogDungeonRouteServiceInterface $combatLogDungeonRouteService
     *
     * @return int
     */
    public function handle(CombatLogDungeonRouteServiceInterface $combatLogDungeonRouteService): int
    {
        $filePath = $this->argument('filePath');
        // Assume error
        $result = -1;

        if (is_dir($filePath)) {
            $this->info(sprintf('%s is a dir, parsing all files in the dir..', $filePath));
            $files = glob(sprintf('%s/*', $filePath));
            foreach ($files as $filePath) {
                $this->createDungeonRouteFromCombatLog($combatLogDungeonRouteService, $filePath);
            }
        } else {
            $this->createDungeonRouteFromCombatLog($combatLogDungeonRouteService, $filePath);
        }

        return $result;
    }

    /**
     * @param CombatLogDungeonRouteServiceInterface $combatLogDungeonRouteService
     * @param string $filePath
     *
     * @return void
     */
    private function createDungeonRouteFromCombatLog(CombatLogDungeonRouteServiceInterface $combatLogDungeonRouteService, string $filePath): void
    {
        $this->info(sprintf('Parsing file %s', $filePath));

        // While have a successful result, keep parsing
        if (!is_file($filePath)) {
            $this->warn('- Is a dir - cannot parse');

            return;
        }

        if (!str_contains($filePath, '_events.txt')) {
            $this->comment(sprintf('- Skipping non-events file %s', $filePath));
            return;
        }

        $dungeonRoutes = $combatLogDungeonRouteService->convertCombatLogToDungeonRoutes($filePath);
        foreach ($dungeonRoutes as $dungeonRoute) {
            $this->comment(sprintf('- Created dungeon route %s (%s)', $dungeonRoute->public_key, __($dungeonRoute->dungeon->name)));
        }
    }
}
