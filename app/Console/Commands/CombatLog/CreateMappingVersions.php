<?php

namespace App\Console\Commands\CombatLog;

use App\Service\CombatLog\CombatLogMappingVersionServiceInterface;
use App\Service\CombatLog\ResultEventDungeonRouteServiceInterface;

class CreateMappingVersions extends BaseCombatLogCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:createmappingversion {filePath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a mapping version for all _events.txt files found in the given directory.';

    /**
     * Execute the console command.
     *
     * @param CombatLogMappingVersionServiceInterface $combatLogMappingVersionService
     *
     * @return int
     */
    public function handle(CombatLogMappingVersionServiceInterface $combatLogMappingVersionService): int
    {
        $filePath = $this->argument('filePath');

        return $this->parseCombatLogRecursively($filePath, function (string $filePath) use ($combatLogMappingVersionService) {
            return $this->createMappingVersionFromCombatLog($combatLogMappingVersionService, $filePath);
        });
    }

    /**
     * @param CombatLogMappingVersionServiceInterface $combatLogMappingVersionService
     * @param string                                  $filePath
     *
     * @return int
     */
    private function createMappingVersionFromCombatLog(CombatLogMappingVersionServiceInterface $combatLogMappingVersionService, string $filePath): int
    {
        $this->info(sprintf('Parsing file %s', $filePath));

        if (!str_contains($filePath, '_events.txt')) {
            $this->comment(sprintf('- Skipping non-events file %s', $filePath));

            return 0;
        }

        $mappingVersion = $combatLogMappingVersionService->createMappingVersionFromDungeonOrRaid($filePath);
        $this->info(
            sprintf(
                '- Created mapping version %s (%s, %d)',
                $mappingVersion->version,
                __($mappingVersion->dungeon->name, [], 'en'),
                $mappingVersion->id
            )
        );

        return 0;
    }
}
