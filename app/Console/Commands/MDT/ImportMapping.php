<?php

namespace App\Console\Commands\MDT;

use App\Logic\MDT\Conversion;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\MDTMappingExportServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use Illuminate\Console\Command;

class ImportMapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mdt:importmapping {dungeon}';

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

        $mappingImportService->importMappingVersionFromMDT($mappingService, Dungeon::where('key', $dungeonKey)->firstOrFail());
    }
}
