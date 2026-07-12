<?php

namespace App\Console\Commands\MapContext;

use App\Console\Commands\MapContext\Traits\ResolvesMapContextScope;
use App\Console\Commands\MapContext\Traits\SavesToFile;
use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\MapContext\MapContextServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class MakeMapContextMappingVersion extends Command
{
    use ResolvesMapContextScope;
    use SavesToFile;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:mapcontextmappingversion
        {--output= : The output folder to place the generated map context in}
        {--scope=all : Which mapping versions to generate map context for, scoped by their dungeon (current-season, rest, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates map context for all mapping versions';

    /**
     * Execute the console command.
     */
    public function handle(
        MapContextServiceInterface $mapContextService,
        SeasonServiceInterface     $seasonService,
    ): int {
        $output = $this->option('output') ?? storage_path('mapcontext');

        $dungeonIds = $this->resolveDungeonIdsForScope((string)$this->option('scope'), $seasonService);
        if ($dungeonIds === null) {
            return self::FAILURE;
        }

        /** @var Collection<int, Dungeon> $dungeonsById */
        $dungeonsById = Dungeon::whereIn('id', $dungeonIds)->get()->keyBy('id');
        /** @var Collection<int, MappingVersion> $mappingVersions */
        $mappingVersions = MappingVersion::whereIn('dungeon_id', $dungeonIds)->get();
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

                $mapContext = $mapContextService->createMapContextMappingVersionData(
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
                    sprintf('let mapContextMappingVersionData = %s;', json_encode($mapContext->toArray())),
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
