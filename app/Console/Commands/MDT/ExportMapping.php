<?php

namespace App\Console\Commands\MDT;

use App\Logic\MDT\Conversion;
use App\Models\Expansion;
use App\Service\MDT\MDTMappingExportServiceInterface;
use Exception;
use Illuminate\Console\Command;

class ExportMapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mdt:exportmapping {expansion} {targetFolder} {--excludeTranslations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports the current mapping of all dungeons to MDT';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(MDTMappingExportServiceInterface $mappingExportService): int
    {
        $expansion           = Expansion::where('shortname', $this->argument('expansion'))->firstOrFail();
        $targetFolder        = $this->argument('targetFolder');
        $excludeTranslations = $this->option('excludeTranslations');

        foreach ($expansion->dungeonsAndRaids as $dungeon) {
            if (!$dungeon->enemies()->exists()) {
                $this->comment(sprintf('Skipping %s, no enemies found', __($dungeon->name)));

                continue;
            }

            $dungeon->load('currentMappingVersion');
            if ($dungeon->currentMappingVersion === null) {
                $this->comment(sprintf('Skipping %s, no current mapping version found', __($dungeon->name)));

                continue;
            }

            $luaString = $mappingExportService->getMDTMappingAsLuaString($dungeon->currentMappingVersion, $excludeTranslations);

            if (!Conversion::hasMDTDungeonName($dungeon->key)) {
                $this->warn(sprintf('Unable to find MDT dungeon for key %s!', $dungeon->key));
            } else {
                $fileName = sprintf('%s/%s.lua', realpath($targetFolder), Conversion::getMDTDungeonName($dungeon->key));

                $this->info(sprintf('Saving %s', $fileName));
                file_put_contents($fileName, $luaString);
            }
        }

        $this->info('Done!');

        return 0;
    }
}
