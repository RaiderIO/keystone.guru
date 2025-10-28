<?php

namespace App\Console\Commands\MapContext;

use App\Console\Commands\MapContext\Traits\SavesToFile;
use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\MapContext\MapContextServiceInterface;
use Illuminate\Console\Command;

class MakeMapContext extends Command
{
    use SavesToFile;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:mapcontext {--output= : The output folder to place the generated map context in}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates map context for a specific mapping version';

    /**
     * Execute the console command.
     */
    public function handle(
        MapContextServiceInterface $mapContextService,
    ): int {
        $output = $this->option('output') ?? storage_path('mapcontext');

        $dungeonsById    = Dungeon::all()->keyBy('id');
        $mappingVersions = MappingVersion::all();
//        $mappingVersions = MappingVersion::where('id', 605)->get();
        $mapFacadeStyles = User::MAP_FACADE_STYLE_ALL;

        $bar = $this->output->createProgressBar($mappingVersions->count() * count($mapFacadeStyles));
        $bar->setFormat('verbose');
        $bar->start();

        foreach ($mappingVersions as $mappingVersion) {
            /** @var Dungeon $dungeon */
            $dungeon = $dungeonsById->get($mappingVersion->dungeon_id);

            foreach ($mapFacadeStyles as $mapFacadeStyle) {
                if (!$mappingVersion->facade_enabled && $mapFacadeStyle === User::MAP_FACADE_STYLE_FACADE) {
                    // Advance progress for each processed output
                    $bar->advance();
                    continue;
                }

                $mapContext = $mapContextService->createMapContextDungeonData(
                // Can't do $mappingVersion->dungeon because of infinite loop
                    $dungeon,
                    $mappingVersion,
                    $mapFacadeStyle,
                );

                $result = $this->saveFileToMapContext(
                    $output,
                    sprintf(
                        'data/%s/%d',
                        $dungeon->slug,
                        $mappingVersion->id,
                    ),
                    sprintf('%s.js', $mapFacadeStyle),
                    sprintf('let mapContextDungeonData = %s;', json_encode($mapContext->toArray())),
                );

                if ($result === false) {
                    $this->error(sprintf('Failed to write map context for mapping version %d', $mappingVersion->id));
                }

                // Advance progress for each processed output
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();

        return 0;
    }
}
