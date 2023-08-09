<?php

namespace App\Service\CombatLog;

use App\Service\CombatLog\Models\ExtractedData;

interface CombatLogDataExtractionServiceInterface
{
    /**
     * @param string $filePath
     * @return ExtractedData
     */
    public function extractData(string $filePath): ExtractedData;
}
