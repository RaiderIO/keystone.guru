<?php

namespace App\Service\Spell\Description\Dtos;

class SpellDescriptionImportResult
{
    public function __construct(
        public readonly string $build,
        public readonly int    $spellCount,
        public readonly int    $describedCount,
        public readonly int    $updatedCount,
    ) {
    }
}
