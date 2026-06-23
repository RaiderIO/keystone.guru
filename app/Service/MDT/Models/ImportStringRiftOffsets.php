<?php

namespace App\Service\MDT\Models;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;

class ImportStringRiftOffsets
{
    /**
     * @var Collection<int, mixed>
     */
    private readonly Collection $mapIcons;

    /**
     * @var Collection<int, mixed>
     */
    private readonly Collection $paths;

    /**
     * @param Collection<int, mixed> $warnings
     * @param array<int, mixed>      $riftOffsets
     */
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

    /**
     * @return Collection<int, mixed>
     */
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

    /**
     * @return array<int, mixed>
     */
    public function getRiftOffsets(): array
    {
        return $this->riftOffsets;
    }

    public function getWeek(): int
    {
        return $this->week;
    }

    /**
     * @return Collection<int, mixed>
     */
    public function getMapIcons(): Collection
    {
        return $this->mapIcons;
    }

    /**
     * @return Collection<int, mixed>
     */
    public function getPaths(): Collection
    {
        return $this->paths;
    }
}
