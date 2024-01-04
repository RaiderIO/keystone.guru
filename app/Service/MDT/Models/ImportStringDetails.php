<?php

namespace App\Service\MDT\Models;

use App\Logic\MDT\Exception\ImportError;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\Dungeon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class ImportStringDetails implements Arrayable
{
    /** @var Collection|ImportWarning[] */
    private Collection $warnings;

    /** @var Collection|ImportError[] */
    private Collection $errors;

    private Dungeon $dungeon;

    /** @var Collection|string[] */
    private Collection $affixes;

    private bool $hasThisWeeksAffixGroup = false;

    private int $pulls = 0;

    private int $paths = 0;

    private int $lines = 0;

    private int $notes = 0;

    private int $enemyForces = 0;

    private int $enemyForcesMax = 0;

    private ?string $faction = null;

    /**
     * @param ImportWarning|Collection $warnings
     * @param Collection               $errors
     * @param Dungeon                  $dungeon
     * @param Collection|string[]      $affixes
     * @param bool                     $hasThisWeeksAffixGroup
     * @param int                      $pulls
     * @param int                      $paths
     * @param int                      $lines
     * @param int                      $notes
     * @param int                      $enemyForces
     * @param int                      $enemyForcesMax
     */
    public function __construct(
        Collection $warnings,
        Collection $errors,
        Dungeon    $dungeon,
        Collection $affixes,
        bool       $hasThisWeeksAffixGroup,
        int        $pulls,
        int        $paths,
        int        $lines,
        int        $notes,
        int        $enemyForces,
        int        $enemyForcesMax)
    {
        $this->warnings               = $warnings;
        $this->errors                 = $errors;
        $this->dungeon                = $dungeon;
        $this->affixes                = $affixes;
        $this->hasThisWeeksAffixGroup = $hasThisWeeksAffixGroup;
        $this->pulls                  = $pulls;
        $this->paths                  = $paths;
        $this->lines                  = $lines;
        $this->notes                  = $notes;
        $this->enemyForces            = $enemyForces;
        $this->enemyForcesMax         = $enemyForcesMax;
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
     * @param string $faction
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
