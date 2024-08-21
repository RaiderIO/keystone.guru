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

    /**
     * Execute the console command.
     */
    public function handle(CombatLogDataExtractionServiceInterface $combatLogDataExtractionService): int
    {
        $filePath = $this->argument('filePath');

        return $this->parseCombatLogRecursively($filePath, fn(string $filePath) => $this->extractData($combatLogDataExtractionService, $filePath));
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
                // sprintf
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
