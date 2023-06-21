<?php

namespace App\Service\MDT\Models;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;

class ImportStringRiftOffsets
{
    private Collection $warnings;

    private Dungeon $dungeon;

    private MappingVersion $mappingVersion;

    private ?int $seasonalIndex;

    private array $riftOffsets;

    private int $week;

    /**
     * @param Collection $warnings
     * @param Dungeon $dungeon
     * @param MappingVersion $mappingVersion
     * @param int|null $seasonalIndex
     * @param array $riftOffsets
     * @param int $week
     */
    public function __construct(Collection $warnings, Dungeon $dungeon, MappingVersion $mappingVersion, ?int $seasonalIndex, array $riftOffsets, int $week)
    {
        $this->warnings       = $warnings;
        $this->dungeon        = $dungeon;
        $this->mappingVersion = $mappingVersion;
        $this->seasonalIndex  = $seasonalIndex;
        $this->riftOffsets    = $riftOffsets;
        $this->week           = $week;
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
}
