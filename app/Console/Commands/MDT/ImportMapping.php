<?php

namespace App\Console\Commands\MDT;

use App\Models\Dungeon;
use App\Models\Season;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use Illuminate\Console\Command;

class ImportMapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mdt:importmapping {dungeon} {force=false}';

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
     * @return void
     * @throws \Exception
     */
    public function handle(MappingServiceInterface $mappingService, MDTMappingImportServiceInterface $mappingImportService)
    {
        $dungeonKey = $this->argument('dungeon');
        $force      = $this->argument('force') === 'true';

        if (is_numeric($dungeonKey)) {
            // If it's an ID we should treat it as a season instead
            $season = Season::findOrFail($dungeonKey);

            foreach ($season->dungeons as $dungeon) {
                try {
                    $mappingImportService->importMappingVersionFromMDT($mappingService, $dungeon, $force);
                } catch (\Exception $exception) {
                    $this->error($exception->getMessage());
                }
            }
        } else {
            $mappingImportService->importMappingVersionFromMDT($mappingService, Dungeon::where('key', $dungeonKey)->firstOrFail(), $force);
        }
    }
}
