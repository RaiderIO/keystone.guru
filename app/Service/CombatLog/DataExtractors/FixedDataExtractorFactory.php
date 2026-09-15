<?php

namespace App\Service\CombatLog\DataExtractors;

use Illuminate\Support\Collection;

/**
 * Hands CombatLogDataExtractionService a caller-supplied extractor set instead of the production pipeline. Installed
 * via Container::instance() by commands that want the parse/dungeon-resolution plumbing but only one extractor
 * (combatlog:extractnpchealth); nothing in the production object graph references it.
 */
class FixedDataExtractorFactory implements DataExtractorFactoryInterface
{
    /**
     * @param Collection<int, DataExtractorInterface> $dataExtractors
     */
    public function __construct(
        private readonly Collection $dataExtractors,
    ) {
    }

    public function createExtractors(): Collection
    {
        return $this->dataExtractors;
    }
}
