<?php

namespace App\Service\MDT\Models;

use App\Logic\MDT\Exception\ImportError;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;

class ImportStringPulls
{
    private int $enemyForces = 0;

    private Collection $killZoneAttributes;

    /**
     * @param int|null $seasonalIndex
     */
    public function __construct(
        private Collection     $warnings,
        private Collection     $errors,
        private Dungeon        $dungeon,
        private MappingVersion $mappingVersion,
        private bool           $isRouteTeeming,
        private ?int           $seasonalIndex,
        private array          $mdtPulls)
    {
        $this->killZoneAttributes = collect();
    }

    /**
     * @return Collection|ImportWarning[]
     */
    public function getWarnings(): Collection
    {
        return $this->warnings;
    }

    /**
     * @return Collection|ImportError[]
     */
    public function getErrors(): Collection
    {
        return $this->errors;
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
     * @return $this
     */
    public function addEnemyForces(int $amount): self
    {
        $this->enemyForces += $amount;

        return $this;
    }

    /**
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
