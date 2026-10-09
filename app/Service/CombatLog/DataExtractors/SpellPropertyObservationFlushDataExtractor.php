<?php

namespace App\Service\CombatLog\DataExtractors;

use App\Logic\CombatLog\BaseEvent;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;

/**
 * Writes the shared spell property observations once every other extractor's afterExtract() has queued its own -
 * must therefore run last.
 */
class SpellPropertyObservationFlushDataExtractor implements DataExtractorInterface
{
    public function __construct(
        private readonly SpellPropertyObservationBuffer $observationBuffer,
    ) {
    }

    public function beforeExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
    }

    public function extractData(
        ExtractedDataResult          $result,
        DataExtractionCurrentDungeon $currentDungeon,
        BaseEvent                    $parsedEvent,
    ): void {
    }

    public function afterExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        $this->observationBuffer->flush();
    }
}
