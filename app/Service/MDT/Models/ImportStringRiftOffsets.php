<?php

namespace App\Service\MDT\Models;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;

class ImportStringRiftOffsets
{
    private readonly Collection $mapIcons;

    private readonly Collection $paths;

    /**
     * @param int|null $seasonalIndex
     */
    public function __construct(
        private readonly Collection     $warnings,
        private readonly Dungeon        $dungeon,
        private readonly MappingVersion $mappingVersion,
        private readonly ?int           $seasonalIndex,
        private readonly array          $riftOffsets,
        private readonly int            $week
    ) {
        $this->mapIcons = collect();
        $this->paths    = collect();
    }

    /**
     * @return Collection
     */
    public function getWarnings(): Collection
    {
        return $this->warnings;
    }

    /**
     * @return Dungeon
     */
    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }

    /**
     * @return MappingVersion
     */
    public function getMappingVersion(): MappingVersion
    {
        return $this->mappingVersion;
    }

    /**
     * @return int|null
     */
    public function getSeasonalIndex(): ?int
    {
        return $this->seasonalIndex;
    }

    /**
     * @return array
     */
    public function getRiftOffsets(): array
    {
        return $this->riftOffsets;
    }

    /**
     * @return int
     */
    public function getWeek(): int
    {
        return $this->week;
    }

    /**
     * @return Collection
     */
    public function getMapIcons(): Collection
    {
        return $this->mapIcons;
    }

    /**
     * @return Collection
     */
    public function getPaths(): Collection
    {
        return $this->paths;
    }
}
