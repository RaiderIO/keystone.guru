<?php

namespace App\Service\CombatLog;

use App\Service\CombatLog\Dtos\ExtractedDataResult;

interface CombatLogDataExtractionServiceInterface
{
    public function extractData(string $filePath): ExtractedDataResult;
}
