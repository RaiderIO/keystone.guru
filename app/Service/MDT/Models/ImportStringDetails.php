<?php

namespace App\Service\MDT\Models;

use App\Logic\MDT\Exception\ImportError;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\Dungeon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class ImportStringDetails implements Arrayable
{
    private ?string $faction = null;

    /**
     * @param ImportWarning|Collection $warnings
     * @param Collection|string[]      $affixes
     */
    public function __construct(private readonly Collection $warnings, private readonly Collection $errors, private readonly Dungeon $dungeon, private readonly Collection $affixes, private readonly bool $hasThisWeeksAffixGroup, private readonly int $pulls, private readonly int $paths, private readonly int $lines, private readonly int $notes, private readonly int $enemyForces, private readonly int $enemyForcesMax)
    {
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

    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }

    public function isHasThisWeeksAffixGroup(): bool
    {
        return $this->hasThisWeeksAffixGroup;
    }

    public function getPulls(): int
    {
        return $this->pulls;
    }

    public function getPaths(): int
    {
        return $this->paths;
    }

    public function getLines(): int
    {
        return $this->lines;
    }

    public function getNotes(): int
    {
        return $this->notes;
    }

    public function getEnemyForces(): int
    {
        return $this->enemyForces;
    }

    public function getEnemyForcesMax(): int
    {
        return $this->enemyForcesMax;
    }

    public function getFaction(): ?string
    {
        return $this->faction;
    }

    public function setFaction(string $faction): ImportStringDetails
    {
        $this->faction = $faction;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'dungeon'                    => __($this->dungeon->name),
            'affixes'                    => $this->affixes,
            'has_this_weeks_affix_group' => $this->hasThisWeeksAffixGroup,
            'pulls'                      => $this->pulls,
            'paths'                      => $this->paths,
            'lines'                      => $this->lines,
            'notes'                      => $this->notes,
            'enemy_forces'               => $this->enemyForces,
            'enemy_forces_max'           => $this->enemyForcesMax,
            'warnings'                   => $this->warnings,
            'errors'                     => $this->errors,
        ];
    }
}
