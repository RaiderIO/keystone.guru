<?php

namespace App\Service\Spell\Cooldown\Dtos;

class SpellCooldownImportResult
{
    public function __construct(
        public readonly string $build,
        public readonly int    $spellCount,
        public readonly int    $presentCount,
        public readonly int    $cooldownUpdatedCount,
        public readonly int    $specializationUpdatedCount,
        public readonly bool   $specializationsImported,
        public readonly int    $wowheadFilledCount,
    ) {
    }
}
