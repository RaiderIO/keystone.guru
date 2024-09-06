<?php

namespace App\Service\CombatLog;

use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Collection;

interface CombatLogDataExtractionServiceInterface
{
    public function extractData(string $filePath): ExtractedDataResult;
}
