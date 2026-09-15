<?php

namespace App\Service\CombatLog\DataExtractors;

use Illuminate\Support\Collection;

interface DataExtractorFactoryInterface
{
    /**
     * The extractors that {@see \App\Service\CombatLog\CombatLogDataExtractionService} runs for every parsed
     * combat log event. The order of the extractors is load-bearing - e.g. CreateMissingNpcDataExtractor must
     * run before extractors that expect the NPC to exist.
     *
     * @return Collection<int, DataExtractorInterface>
     */
    public function createExtractors(): Collection;
}
