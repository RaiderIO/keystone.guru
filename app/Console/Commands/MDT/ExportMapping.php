<?php

namespace App\Console\Commands\MDT;

use App\Logic\MDT\Conversion;
use App\Models\Expansion;
use App\Service\MDT\MDTMappingExportServiceInterface;
use Illuminate\Console\Command;

class ExportMapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mdt:exportmapping {expansion} {targetFolder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports the current mapping of all dungeons to MDT';

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
    public function handle(MDTMappingExportServiceInterface $mappingExportService)
    {
        $expansion    = Expansion::where('shortname', $this->argument('expansion'))->firstOrFail();
        $targetFolder = $this->argument('targetFolder');

        foreach ($expansion->dungeons as $dungeon) {
            if (!$dungeon->enemies()->exists()) {
                $this->comment(sprintf('Skipping %s, no enemies found', __($dungeon->name)));
                continue;
            }

            $luaString = $mappingExportService->getMDTMappingAsLuaString($dungeon->getCurrentMappingVersion());

            $name = Conversion::getMDTDungeonName($dungeon->key);
            if ($name === null) {
                throw new \Exception(sprintf('Unable to find MDT dungeon for key %s!', $dungeon->key));
            }
            $fileName = sprintf('%s/%s.lua', $targetFolder, $name);

            $this->info(sprintf('Saving %s', $fileName));
            file_put_contents($fileName, $luaString);
        }

        $this->info('Done!');
    }
}
