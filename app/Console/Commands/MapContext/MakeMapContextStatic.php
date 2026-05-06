<?php

namespace App\Console\Commands\MapContext;

use App\Console\Commands\MapContext\Traits\SavesToFile;
use App\Service\MapContext\MapContextServiceInterface;
use Illuminate\Console\Command;

class MakeMapContextStatic extends Command
{
    use SavesToFile;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:mapcontextstatic {--output= : The output folder to place the generated static map context in}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a static map context for all locales';

    /**
     * Execute the console command.
     */
    public function handle(
        MapContextServiceInterface $mapContextService,
    ): int {
        $output = $this->option('output') ?? storage_path('mapcontext');

        foreach (language()->allowed() as $locale => $languageName) {
            $staticMapContext = $mapContextService->createMapContextStaticData(
                $locale,
            );

            $result = $this->saveFileToMapContext(
                $output,
                'static',
                sprintf(
                    '%s.js',
                    $locale,
                ),
                sprintf('let mapContextStaticData = %s;', json_encode($staticMapContext->toArray())),
            );

            if ($result === false) {
                $this->error(sprintf('Failed to write map context for locale %s', $locale));
            }
        }

        return 0;
    }
}
