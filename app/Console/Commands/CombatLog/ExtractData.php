<?php

namespace App\Console\Commands\CombatLog;

use App\Models\CombatLog\ParsedCombatLog;
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

    private array $combinedDataResult = [];

    /**
     * Execute the console command.
     */
    public function handle(CombatLogDataExtractionServiceInterface $combatLogDataExtractionService): int
    {
        $filePath = $this->argument('filePath');

        $parseResult = $this->parseCombatLogRecursively($filePath, fn(string $filePath) => $this->extractData($combatLogDataExtractionService, $filePath));

        $this->info('Total result:');
        foreach ($this->combinedDataResult as $key => $value) {
            $this->info(sprintf(' - %s: %s', $key, $value));
        }

        return $parseResult;
    }

    private function extractData(CombatLogDataExtractionServiceInterface $combatLogDataExtractionService, string $filePath): int
    {
        $this->info(sprintf('Parsing file %s', $filePath));

        if (ParsedCombatLog::where('combat_log_path', $filePath)->exists()) {
            $this->warn(
                '- Data already extracted for this file'
            );

            return 0;
        }

        $result = $combatLogDataExtractionService->extractData($filePath);
        $data   = array_filter($result->toArray());
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (!isset($this->combinedDataResult[$key])) {
                    $this->combinedDataResult[$key] = 0;
                }
                $this->combinedDataResult[$key] += $value;

                $this->info(sprintf('- %s: %s', $key, $value));
            }
        } else {
            $this->comment(
                '- Did not find any data to update'
            );
        }

        ParsedCombatLog::insert([
            'combat_log_path' => $filePath,
            'extracted_data'  => true,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return 0;
    }
}
