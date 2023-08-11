<?php

namespace App\Service\CombatLog;

use App\Service\CombatLog\Models\ExtractedDataResult;

interface CombatLogDataExtractionServiceInterface
{
    /**
     * @param string $filePath
     *
     * @return ExtractedDataResult
     */
    public function extractData(string $filePath): ExtractedDataResult;
}
