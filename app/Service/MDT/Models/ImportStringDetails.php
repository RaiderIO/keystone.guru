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
    public function __construct(private Collection $warnings, private Collection $errors, private Dungeon $dungeon, private Collection $affixes, private bool $hasThisWeeksAffixGroup, private int $pulls, private int $paths, private int $lines, private int $notes, private int $enemyForces, private int $enemyForcesMax)
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

    /**
     * @return Dungeon
     */
    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }

    /**
     * @return bool
     */
    public function isHasThisWeeksAffixGroup(): bool
    {
        return $this->hasThisWeeksAffixGroup;
    }

    /**
     * @return int
     */
    public function getPulls(): int
    {
        return $this->pulls;
    }

    /**
     * @return int
     */
    public function getPaths(): int
    {
        return $this->paths;
    }

    /**
     * @return int
     */
    public function getLines(): int
    {
        return $this->lines;
    }

    /**
     * @return int
     */
    public function getNotes(): int
    {
        return $this->notes;
    }

    /**
     * @return int
     */
    public function getEnemyForces(): int
    {
        return $this->enemyForces;
    }

    /**
     * @return int
     */
    public function getEnemyForcesMax(): int
    {
        return $this->enemyForcesMax;
    }

    /**
     * @return string|null
     */
    public function getFaction(): ?string
    {
        return $this->faction;
    }

    /**
     * @return ImportStringDetails
     */
    public function setFaction(string $faction): ImportStringDetails
    {
        $this->faction = $faction;

        return $this;
    }


    /**
     * @return array
     */
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
