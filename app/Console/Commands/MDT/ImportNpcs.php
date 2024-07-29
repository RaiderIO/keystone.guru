<?php

namespace App\Console\Commands\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use Exception;
use Illuminate\Console\Command;

class ImportNpcs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mdt:importnpcs {--dungeon=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports all NPCs from MDT to the database';

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(
        CacheServiceInterface            $cacheService,
        CoordinatesServiceInterface      $coordinatesService,
        MDTMappingImportServiceInterface $mappingImportService): void
    {
        $dungeonKey = $this->option('dungeon');

        $dungeons = collect();

        if ($dungeonKey !== null) {
            $dungeon = Dungeon::where('key', $dungeonKey)->firstOrFail();
            $dungeon->setRelation('currentMappingVersion', $dungeon->currentMappingVersion()->first());
            $dungeon->setRelation('npcs', $dungeon->npcs()->get());

            $dungeons->push($dungeon);
        } else {
            $dungeons = Dungeon::all();
        }

        foreach ($dungeons as $dungeon) {
            /** @var Dungeon $dungeon */
            if (!Conversion::hasMDTDungeonName($dungeon->key)) {
                continue;
            }

            // Cannot do ->with('npcs') here - it won't load the relationship properly due to orWhere(dungeon_id = -1)
            $dungeon->setRelation('currentMappingVersion', $dungeon->currentMappingVersion()->first());
            $dungeon->setRelation('npcs', $dungeon->npcs()->get());

            $mdtDungeon = new MDTDungeon($cacheService, $coordinatesService, $dungeon);

            $mappingImportService->importNpcsDataFromMDT($mdtDungeon, $dungeon);
        }
    }
}
