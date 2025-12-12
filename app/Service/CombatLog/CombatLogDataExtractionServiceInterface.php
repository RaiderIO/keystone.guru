<?php

namespace App\Service\CombatLog;

use App\Models\CombatLog\CombatLogAnalyze;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;

interface CombatLogDataExtractionServiceInterface
{
    public function extractData(string $filePath, ?callable $onProcessLine = null): ExtractedDataResult;

    /**
     * Analyzes a combat log and extracts all relevant information from it.
     */
    public function extractDataAsync(string $filePath, CombatLogAnalyze $combatLogAnalyze): ?ExtractedDataResult;
}
