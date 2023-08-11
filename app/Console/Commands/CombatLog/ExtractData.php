<?php

namespace App\Console\Commands\CombatLog;

use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;

class ExtractData extends BaseCombatLogCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:extractdata {filePath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extracts data such as floor bounding boxes, enemy health etc and applies it to the current mapping/static data.';

    /**
     * Execute the console command.
     *
     * @param CombatLogDataExtractionServiceInterface $combatLogDataExtractionService
     *
     * @return int
     */
    public function handle(CombatLogDataExtractionServiceInterface $combatLogDataExtractionService): int
    {
        $filePath = $this->argument('filePath');

        return $this->parseCombatLogRecursively($filePath, function (string $filePath) use ($combatLogDataExtractionService) {
            return $this->extractData($combatLogDataExtractionService, $filePath);
        });
    }

    /**
     * @param CombatLogDataExtractionServiceInterface $combatLogDataExtractionService
     * @param string                                  $filePath
     *
     * @return int
     */
    private function extractData(CombatLogDataExtractionServiceInterface $combatLogDataExtractionService, string $filePath): int
    {
        $this->info(sprintf('Parsing file %s', $filePath));

        $result = $combatLogDataExtractionService->extractData($filePath);
        if( $result->hasUpdatedData() ) {
            $this->info(
                sprintf(
                    'Updated %d floors, %d floor connections, %d npcs',
                    $result->getUpdatedFloors(),
                    $result->getUpdatedFloorConnections(),
                    $result->getUpdatedNpcs()
                )
            );
        } else {
            $this->comment(
                'Did not find any data to update'
            );
        }

        return 0;
    }
}
