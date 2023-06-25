<?php

namespace App\Service\MDT\Models;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;

class ImportStringPulls
{
    private Collection $warnings;
    private Dungeon $dungeon;
    private MappingVersion $mappingVersion;
    private bool $isRouteTeeming;
    private ?int $seasonalIndex;
    private array $mdtPulls;

    private int $enemyForces = 0;

    private Collection $killZoneAttributes;

    /**
     * @param Collection $warnings
     * @param Dungeon $dungeon
     * @param MappingVersion $mappingVersion
     * @param bool $isRouteTeeming
     * @param int|null $seasonalIndex
     * @param array $mdtPulls
     */
    public function __construct(
        Collection     $warnings,
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        bool           $isRouteTeeming,
        ?int            $seasonalIndex,
        array          $mdtPulls)
    {
        $this->warnings       = $warnings;
        $this->dungeon        = $dungeon;
        $this->mappingVersion = $mappingVersion;
        $this->isRouteTeeming = $isRouteTeeming;
        $this->seasonalIndex  = $seasonalIndex;
        $this->mdtPulls       = $mdtPulls;

        $this->killZoneAttributes = collect();
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
     * @return bool
     */
    public function isRouteTeeming(): bool
    {
        return $this->isRouteTeeming;
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
    public function getMdtPulls(): array
    {
        return $this->mdtPulls;
    }

    /**
     * @param int $amount
     * @return $this
     */
    public function addEnemyForces(int $amount): self
    {
        $this->enemyForces += $amount;

        return $this;
    }

    /**
     * @param array $attributes
     * @return self
     */
    public function addKillZoneAttributes(array $attributes): self
    {
        $this->killZoneAttributes->push($attributes);

        return $this;
    }

    /**
     * @return int
     */
    public function getEnemyForces(): int
    {
        return $this->enemyForces;
    }

    /**
     * @return Collection
     */
    public function getKillZoneAttributes(): Collection
    {
        return $this->killZoneAttributes;
    }
}
