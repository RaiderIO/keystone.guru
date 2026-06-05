<?php

namespace App\Service\CombatLog\DataExtractors\SpellDataCollectors;

use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;

interface SpellDataCollectorInterface
{
    public function beforeCollect(string $combatLogFilePath): void;

    public function afterCollect(ExtractedDataResult $result, string $combatLogFilePath): void;
}
