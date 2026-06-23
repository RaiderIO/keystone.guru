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

    /**
     * @var Collection<int, mixed>
     */
    private readonly Collection $killZoneAttributes;

    /**
     * @param Collection<int, ImportWarning> $warnings
     * @param Collection<int, ImportError>   $errors
     * @param array<int, mixed>              $mdtPulls
     */
    public function __construct(
        private readonly Collection     $warnings,
        private readonly Collection     $errors,
        private readonly Dungeon        $dungeon,
        private readonly MappingVersion $mappingVersion,
        private readonly bool           $isRouteTeeming,
        private readonly ?int           $seasonalIndex,
        private readonly array          $mdtPulls,
    ) {
        $this->killZoneAttributes = collect();
    }

    /**
     * @return Collection<int, ImportWarning>
     */
    public function getWarnings(): Collection
    {
        return $this->warnings;
    }

    /**
     * @return Collection<int, ImportError>
     */
    public function getErrors(): Collection
    {
        return $this->errors;
    }

    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }

    public function getMappingVersion(): MappingVersion
    {
        return $this->mappingVersion;
    }

    public function isRouteTeeming(): bool
    {
        return $this->isRouteTeeming;
    }

    public function getSeasonalIndex(): ?int
    {
        return $this->seasonalIndex;
    }

    /**
     * @return array<int, mixed>
     */
    public function getMdtPulls(): array
    {
        return $this->mdtPulls;
    }

    public function addEnemyForces(int $amount): self
    {
        $this->enemyForces += $amount;

        return $this;
    }

    /**
     * @param array<int, mixed> $attributes
     */
    public function addKillZoneAttributes(array $attributes): self
    {
        $this->killZoneAttributes->push($attributes);

        return $this;
    }

    public function getEnemyForces(): int
    {
        return $this->enemyForces;
    }

    /**
     * @return Collection<int, mixed>
     */
    public function getKillZoneAttributes(): Collection
    {
        return $this->killZoneAttributes;
    }
}
