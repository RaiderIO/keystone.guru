<?php

namespace App\Service\CombatLog\Models\ActivePull;

use Carbon\Carbon;
use Illuminate\Support\Collection;

abstract class ActivePull
{
    /** @var Collection */
    protected Collection $enemiesKilled;

    /** @var Collection */
    protected Collection $spellsCast;

    /** @var Collection */
    protected Collection $enemiesInCombat;

    public function __construct()
    {
        $this->enemiesKilled   = collect();
        $this->spellsCast      = collect();
        $this->enemiesInCombat = collect();
    }

    /**
     * @param Carbon $timestamp
     * @return float
     */
    abstract function getAverageHPPercentAt(Carbon $timestamp): float;

    /**
     * @return Collection
     */
    public function getEnemiesKilled(): Collection
    {
        return $this->enemiesKilled;
    }

    /**
     * @return Collection
     */
    public function getSpellsCast(): Collection
    {
        return $this->spellsCast;
    }

    /**
     * @return Collection
     */
    public function getEnemiesInCombat(): Collection
    {
        return $this->enemiesInCombat;
    }

    /**
     * @param string $guid
     * @param        $enemy
     * @return $this
     */
    public function enemyKilled(string $guid, $enemy): ActivePull
    {
        $this->enemiesInCombat->forget($guid);
        $this->enemiesKilled->put($guid, $enemy);

        return $this;
    }

    /**
     * @param int $spellId
     * @return $this
     */
    public function addSpell(int $spellId): ActivePull
    {
        $this->spellsCast->push($spellId);

        return $this;
    }

    /**
     * @param string $guid
     * @param        $enemy
     * @return $this
     */
    public function enemyEngaged(string $guid, $enemy): ActivePull
    {
        $this->enemiesInCombat->put($guid, $enemy);

        return $this;
    }

    /**
     * @param string $uniqueUid
     * @return bool
     */
    public function isEnemyInCombat(string $uniqueUid): bool
    {
        $result = false;

        foreach($this->enemiesInCombat as $guid => $npc) {
            if ($guid === $uniqueUid) {
                $result = true;
                break;
            }
        }

        return $result;
    }
}