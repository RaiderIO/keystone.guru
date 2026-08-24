<?php

namespace App\Console\Commands\MDT;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Service\MDT\MDTMappingImportServiceInterface;
use Exception;
use Illuminate\Console\Command;

/**
 * Accepts MDT's mapping onto a dungeon's pending mapping version, rather than importing it into a new one
 * (#4281). Run this once MDT has shipped the changes we submitted and the MDT cross-check tests
 * (MDTNpcMappingCoverageTest, CoordinatesServiceTest) agree with our mapping again - it stamps MDT's mapping
 * hash and addon version onto the mapping version and clears `mdt_changes_pending`, keeping every route
 * where it is instead of bumping the dungeon to a new mapping version with identical content.
 */
class AcceptMapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mdt:acceptmapping {dungeon : A dungeon key, or a season id to accept every pending dungeon in that season} {gameVersion} {--force : Accept even though MDT\'s mapping hash has not changed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Accepts MDT\'s mapping onto a dungeon\'s mapping version that was awaiting MDT acceptance of our changes';

    /**
     * @throws Exception
     */
    public function handle(MDTMappingImportServiceInterface $mappingImportService): int
    {
        $dungeonKey     = $this->argument('dungeon');
        $gameVersionKey = $this->argument('gameVersion');
        $force          = (bool)$this->option('force');

        $gameVersion = GameVersion::firstWhere('key', $gameVersionKey);
        if ($gameVersion === null) {
            throw new Exception(sprintf('Game version %s not found', $gameVersionKey));
        }

        if (is_numeric($dungeonKey)) {
            // Same convention as mdt:importmapping - a numeric argument is a season, not a dungeon
            $season   = Season::findOrFail($dungeonKey);
            $accepted = 0;

            foreach ($season->dungeons as $dungeon) {
                try {
                    $this->accept($mappingImportService, $dungeon, $gameVersion, $force);
                    $accepted++;
                } catch (Exception $exception) {
                    // A dungeon that has nothing to accept is the normal case in a season-wide run
                    $this->line($exception->getMessage());
                }
            }

            $this->info(sprintf('Accepted MDT\'s mapping for %d dungeon(s)', $accepted));

            return $accepted > 0 ? self::SUCCESS : self::FAILURE;
        }

        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::where('key', $dungeonKey)->firstOrFail();

        $this->accept($mappingImportService, $dungeon, $gameVersion, $force);

        return self::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function accept(
        MDTMappingImportServiceInterface $mappingImportService,
        Dungeon                          $dungeon,
        GameVersion                      $gameVersion,
        bool                             $force,
    ): void {
        $mappingVersion = $mappingImportService->acceptMDTMappingForPendingMappingVersion($dungeon, $gameVersion, $force);

        $this->info(sprintf(
            'Accepted MDT\'s mapping for %s onto mapping version v%d (%d) - routes stay where they are',
            $dungeon->key,
            $mappingVersion->version,
            $mappingVersion->id,
        ));
    }
}
