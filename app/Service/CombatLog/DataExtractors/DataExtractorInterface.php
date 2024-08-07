<?php

namespace App\Service\CombatLog\DataExtractors;

use App\Logic\CombatLog\BaseEvent;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;

interface DataExtractorInterface
{
    public function extractData(ExtractedDataResult $result, DataExtractionCurrentDungeon $currentDungeon, BaseEvent $parsedEvent): void;
}
