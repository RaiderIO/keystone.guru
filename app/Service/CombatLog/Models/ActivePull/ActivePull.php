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

    /**
     * @var bool To prevent chain pulls from being killed before the original pull, we defer creating the chain pull
     * until the OG pull is killed. Meanwhile, the chain pull is marked as completed and new pulls are created until
     * then instead of new enemies being added to the chain pull every time.
     */
    private bool $isCompleted;

    public function __construct()
    {
        $this->enemiesKilled   = collect();
        $this->spellsCast      = collect();
        $this->enemiesInCombat = collect();

        $this->isCompleted = false;
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

        if ($this->enemiesInCombat->isEmpty()) {
            $this->isCompleted = true;
        }

        return $this;
    }

    /**
     * @param int $spellId
     * @return $this
     */
    public function addSpell(int $spellId): ActivePull
    {
        // Do not add duplicate spells to the same pull
        if ($this->spellsCast->search($spellId) === false) {
            $this->spellsCast->push($spellId);
        }

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
        return $this->enemiesInCombat->has($uniqueUid);
    }

    /**
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    /**
     * @param ActivePull $activePull
     * @return void
     */
    public function merge(ActivePull $activePull): void
    {
        $this->enemiesInCombat = $this->enemiesInCombat->merge($activePull->enemiesInCombat);
        $this->enemiesKilled = $this->enemiesKilled->merge($activePull->enemiesKilled);
    }
}