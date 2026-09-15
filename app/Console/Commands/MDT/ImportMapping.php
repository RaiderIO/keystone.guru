<?php

namespace App\Console\Commands\MDT;

use App\Logic\MDT\Entity\MDTMapPOI;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ImportMapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mdt:importmapping {dungeon} {gameVersion} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports the current mapping of all dungeons to MDT';

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(
        MappingServiceInterface          $mappingService,
        MDTMappingImportServiceInterface $mappingImportService,
    ): void {
        $dungeonKey     = $this->argument('dungeon');
        $gameVersionKey = $this->argument('gameVersion');
        $force          = (bool)$this->option('force');

        $gameVersion = GameVersion::firstWhere('key', $gameVersionKey);
        if ($gameVersion === null) {
            throw new Exception(sprintf('Game version %s not found', $gameVersionKey));
        }

        /** @var Collection<int, Dungeon> $dungeons */
        $dungeons = new Collection();

        if (is_numeric($dungeonKey)) {
            // If it's an ID we should treat it as a season instead
            $season = Season::findOrFail($dungeonKey);

            // Cannot do ->with('npcs') here - it won't load the relationship properly due to orWhere(dungeon_id = -1)
            foreach ($season->dungeons as $dungeon) {
                $dungeons->push($dungeon);

                try {
                    $dungeon->setRelation('npcs', $dungeon->npcs()->get());
                    $mappingImportService->importMappingVersionFromMDT($mappingService, $dungeon, $gameVersion, $force);
                } catch (Exception $exception) {
                    $this->error($exception->getMessage());
                }
            }
        } else {
            // Cannot do ->with('npcs') here - it won't load the relationship properly due to orWhere(dungeon_id = -1)
            /** @var Dungeon $dungeon */
            $dungeon = Dungeon::where('key', $dungeonKey)->firstOrFail();
            $dungeons->push($dungeon);

            $dungeon->setRelation('npcs', $dungeon->npcs()->get());

            $mappingImportService->importMappingVersionFromMDT($mappingService, $dungeon, $gameVersion, $force);
        }

        $this->renderUnhandledMapPOIs($mappingImportService, $dungeons);
    }

    /**
     * Reports every POI MDT draws that we have no map icon type for. These are read straight from MDT rather
     * than from the import, so they show up even when the import no-ops on an unchanged mapping hash - the
     * gap exists either way, and used to be entirely invisible (#3993).
     *
     * @param Collection<int, Dungeon> $dungeons
     */
    private function renderUnhandledMapPOIs(
        MDTMappingImportServiceInterface $mappingImportService,
        Collection                       $dungeons,
    ): void {
        $rows                  = [];
        $unhandledMapPOIsTotal = 0;

        foreach ($dungeons as $dungeon) {
            try {
                $unhandledMapPOIs = $mappingImportService->getUnhandledMapPOIs($dungeon);
            } catch (Exception $exception) {
                $this->error($exception->getMessage());

                continue;
            }

            $unhandledMapPOIsTotal += $unhandledMapPOIs->count();

            // One row per distinct item rather than per POI - the same item is usually placed several times
            $unhandledMapPOIsByItem = $unhandledMapPOIs->groupBy(
                static fn(MDTMapPOI $mdtMapPOI): string => sprintf(
                    '%s-%s-%s',
                    $mdtMapPOI->getType()->value,
                    $mdtMapPOI->getSpellId() ?? '',
                    $mdtMapPOI->getTextureFileDataId() ?? '',
                ),
            );

            foreach ($unhandledMapPOIsByItem as $unhandledMapPOIsForItem) {
                /** @var MDTMapPOI $mdtMapPOI */
                $mdtMapPOI = $unhandledMapPOIsForItem->first();

                $rows[] = [
                    $dungeon->key,
                    $mdtMapPOI->getType()->value,
                    $mdtMapPOI->getSpellId() ?? '-',
                    $mdtMapPOI->getTextureFileDataId() ?? '-',
                    $unhandledMapPOIsForItem->count(),
                    $mdtMapPOI->getSpellId() === null
                        ? '-'
                        : sprintf('https://www.wowhead.com/spell=%d', $mdtMapPOI->getSpellId()),
                ];
            }
        }

        if ($rows === []) {
            return;
        }

        $this->newLine();
        $this->error(sprintf(
            '%d MDT map POI(s) across %d distinct item(s) have no map icon type and were NOT imported:',
            $unhandledMapPOIsTotal,
            count($rows),
        ));
        $this->table(
            ['Dungeon', 'MDT type', 'Spell ID', 'Texture ID', 'Count', 'Wowhead'],
            $rows,
        );
        $this->line('Run <comment>mapicon:downloadmdtitemicons</comment> for these dungeons to fetch their icons, then add a map icon type for each.');
    }
}
