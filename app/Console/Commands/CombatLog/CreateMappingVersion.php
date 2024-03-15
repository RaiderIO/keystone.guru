<?php

namespace App\Console\Commands\CombatLog;

use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\CombatLogMappingVersionServiceInterface;

class CreateMappingVersion extends BaseCombatLogCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:createmappingversion {filePath} {--mappingVersion=} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a mapping version for all _events.txt files found in the given directory.';

    /**
     * Execute the console command.
     */
    public function handle(CombatLogMappingVersionServiceInterface $combatLogMappingVersionService): int
    {
        $filePath         = $this->argument('filePath');
        $mappingVersionId = $this->option('mappingVersion');

        $mappingVersion = null;
        if (is_numeric($mappingVersionId)) {
            $mappingVersion = MappingVersion::findOrFail($mappingVersionId);
        }

        return $this->parseCombatLogRecursively($filePath, fn(string $filePath) => $this->createMappingVersionFromCombatLog($combatLogMappingVersionService, $filePath, $mappingVersion));
    }

    private function createMappingVersionFromCombatLog(
        CombatLogMappingVersionServiceInterface $combatLogMappingVersionService,
        string                                  $filePath,
        ?MappingVersion                         $mappingVersion = null): int
    {
        $this->info(sprintf('Parsing file %s', $filePath));

        if (!str_contains($filePath, '_events.txt')) {
            $this->comment(sprintf('- Skipping non-events file %s', $filePath));

            return 0;
        }

        $hasMappingVersion = $mappingVersion !== null;

        $mappingVersion = $combatLogMappingVersionService->createMappingVersionFromDungeonOrRaid($filePath, $mappingVersion);
        $this->info(
            sprintf(
                '- %s mapping version %s (%s, %d, %d enemies)',
                $hasMappingVersion ? 'Updated' : 'Created',
                $mappingVersion->version,
                __($mappingVersion->dungeon->name, [], 'en_US'),
                $mappingVersion->id,
                $mappingVersion->enemies()->count(),
            )
        );

        return 0;
    }
}
