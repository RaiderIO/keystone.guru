<?php

namespace App\Console\Commands\CombatLog;

use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\CombatLogMappingVersionServiceInterface;
use App\Service\CombatLog\Exceptions\DungeonHasNoNpcsException;

class CreateMappingVersion extends BaseCombatLogCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:createmappingversion {filePath} {gameVersion} {--enemyConnections} {--mappingVersion=} ';

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
        $gameVersionKey   = $this->argument('gameVersion');
        $mappingVersionId = $this->option('mappingVersion');
        $enemyConnections = (bool)$this->option('enemyConnections');

        $gameVersion    = GameVersion::firstWhere('key', $gameVersionKey);
        $mappingVersion = null;
        if (is_numeric($mappingVersionId)) {
            $mappingVersion = MappingVersion::findOrFail($mappingVersionId);
        }

        return $this->parseCombatLogRecursively(
            $filePath,
            fn(string $filePath) => $this->createMappingVersionFromCombatLog(
                $combatLogMappingVersionService,
                $filePath,
                $gameVersion,
                $mappingVersion,
                $enemyConnections,
            ),
        );
    }

    private function createMappingVersionFromCombatLog(
        CombatLogMappingVersionServiceInterface $combatLogMappingVersionService,
        string                                  $filePath,
        GameVersion                             $gameVersion,
        ?MappingVersion                         $mappingVersion = null,
        bool                                    $enemyConnections = false,
    ): int {
        $this->info(sprintf('Parsing file %s', $filePath));

        if (!str_contains($filePath, '_events.txt')) {
            $this->comment(sprintf('- Skipping non-events file %s', $filePath));

            return 0;
        }

        $hasMappingVersion = $mappingVersion !== null;

        try {
            $mappingVersion = $combatLogMappingVersionService->createMappingVersionFromDungeonOrRaid(
                $filePath,
                $gameVersion,
                $mappingVersion,
                $enemyConnections,
            );
        } catch (DungeonHasNoNpcsException $dungeonHasNoNpcsException) {
            // The message is actionable on its own - don't bury it in a stack trace (#4354)
            $this->error(sprintf('- %s', $dungeonHasNoNpcsException->getMessage()));

            return 0;
        }

        if ($mappingVersion === null) {
            $this->error(sprintf('Failed to create mapping version: %s', $filePath));

            return 0;
        }

        $enemyCount    = $mappingVersion->enemies()->count();
        $resultMessage = sprintf(
            '- %s mapping version %s (%s, %d, %d enemies)',
            $hasMappingVersion ? 'Updated' : 'Created',
            $mappingVersion->version,
            __($mappingVersion->dungeon->name, [], 'en_US'),
            $mappingVersion->id,
            $enemyCount,
        );

        if ($enemyCount === 0) {
            // The dungeon does have NPCs, but not the ones in this combat log - so every enemy was skipped
            // anyway. Report it as the failed import it is rather than as a successful one (#4354).
            $this->error($resultMessage);
            $this->error(
                sprintf(
                    '- No enemies were imported! None of the NPCs in this combat log are attached to %s - run `php artisan combatlog:extractdata %s` first to create them.',
                    __($mappingVersion->dungeon->name, [], 'en_US'),
                    $filePath,
                ),
            );
        } else {
            $this->info($resultMessage);
        }

        return 0;
    }
}
