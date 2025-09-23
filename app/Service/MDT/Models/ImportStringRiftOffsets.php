<?php

namespace App\Service\MDT\Models;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;

class ImportStringRiftOffsets
{
    private readonly Collection $mapIcons;

    private readonly Collection $paths;

    public function __construct(
        private readonly Collection     $warnings,
        private readonly Dungeon        $dungeon,
        private readonly MappingVersion $mappingVersion,
        private readonly ?int           $seasonalIndex,
        private readonly array          $riftOffsets,
        private readonly int            $week,
    ) {
        $this->mapIcons = collect();
        $this->paths    = collect();
    }

    public function getWarnings(): Collection
    {
        return $this->warnings;
    }

    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }

    public function getMappingVersion(): MappingVersion
    {
        return $this->mappingVersion;
    }

    public function getSeasonalIndex(): ?int
    {
        return $this->seasonalIndex;
    }

    public function getRiftOffsets(): array
    {
        return $this->riftOffsets;
    }

    public function getWeek(): int
    {
        return $this->week;
    }

    public function getMapIcons(): Collection
    {
        return $this->mapIcons;
    }

    public function getPaths(): Collection
    {
        return $this->paths;
    }
}
