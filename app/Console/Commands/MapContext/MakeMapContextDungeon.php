<?php

namespace App\Console\Commands\MapContext;

use App\Console\Commands\MapContext\Traits\SavesToFile;
use App\Models\Dungeon;
use App\Service\MapContext\MapContextServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class MakeMapContextDungeon extends Command
{
    use SavesToFile;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:mapcontextdungeon {--output= : The output folder to place the generated map context in}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates map context for all dungeons';

    /**
     * Execute the console command.
     */
    public function handle(
        MapContextServiceInterface $mapContextService,
    ): int {
        $output = $this->option('output') ?? storage_path('mapcontext');

        /** @var Collection<Dungeon> $dungeonsById */
        $dungeonsById     = Dungeon::all()->keyBy('id');
        $allowedLanguages = language()->allowed();

        $bar = $this->output->createProgressBar($dungeonsById->count() * count($allowedLanguages));
        $bar->setFormat('verbose');
        $bar->start();

        foreach ($dungeonsById as $dungeon) {
            foreach ($allowedLanguages as $locale => $languageName) {
                $mapContextDungeonData = $mapContextService->createMapContextDungeonData(
                    $dungeon,
                    $locale,
                );

                $result = $this->saveFileToMapContext(
                    $output,
                    sprintf(
                        'data/%s',
                        $dungeon->slug,
                    ),
                    sprintf(
                        '%s.js',
                        $locale,
                    ),
                    sprintf('let mapContextDungeonData = %s;', json_encode($mapContextDungeonData->toArray())),
                );

                if ($result === false) {
                    $this->error(sprintf('Failed to write map context for dungeon %s, locale %s', $dungeon->slug, $locale));
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
