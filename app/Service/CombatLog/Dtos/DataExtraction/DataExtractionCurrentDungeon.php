<?php

namespace App\Service\CombatLog\Dtos\DataExtraction;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;

readonly class DataExtractionCurrentDungeon
{
    public function __construct(
        public Dungeon     $dungeon,
        public ?int        $keyLevel = null,
        public ?AffixGroup $affixGroup = null,
    ) {
    }
}
