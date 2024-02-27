<?php

namespace App\Console\Commands\MDT;

use App\Models\Dungeon;
use App\Models\Season;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use Exception;
use Illuminate\Console\Command;

class ImportMapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mdt:importmapping {dungeon} {--force=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports the current mapping of all dungeons to MDT';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(MappingServiceInterface $mappingService, MDTMappingImportServiceInterface $mappingImportService): void
    {
        $dungeonKey = $this->argument('dungeon');
        $force      = (bool)$this->option('force');

        if (is_numeric($dungeonKey)) {
            // If it's an ID we should treat it as a season instead
            $season = Season::findOrFail($dungeonKey);

            // Cannot do ->with('npcs') here - it won't load the relationship properly due to orWhere(dungeon_id = -1)
            foreach ($season->dungeons as $dungeon) {
                try {
                    $dungeon->setRelation('currentMappingVersion', $dungeon->currentMappingVersion()->first());
                    $dungeon->setRelation('npcs', $dungeon->npcs()->get());
                    $mappingImportService->importMappingVersionFromMDT($mappingService, $dungeon, $force);
                } catch (Exception $exception) {
                    $this->error($exception->getMessage());
                }
            }
        } else {
            // Cannot do ->with('npcs') here - it won't load the relationship properly due to orWhere(dungeon_id = -1)
            /** @var Dungeon $dungeon */
            $dungeon = Dungeon::where('key', $dungeonKey)->firstOrFail();

            $dungeon->setRelation('currentMappingVersion', $dungeon->currentMappingVersion()->first());
            $dungeon->setRelation('npcs', $dungeon->npcs()->get());

            $mappingImportService->importMappingVersionFromMDT($mappingService, $dungeon, $force);
        }
    }
}
