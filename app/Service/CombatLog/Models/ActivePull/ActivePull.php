<?php

namespace App\Service\CombatLog\Models\ActivePull;

use Carbon\Carbon;
use Illuminate\Support\Collection;

abstract class ActivePull
{
    /** @var Collection|ActivePullEnemy[] */
    protected Collection $enemiesInCombat;

    /** @var Collection|ActivePullEnemy[] */
    protected Collection $enemiesKilled;

    /** @var Collection */
    protected Collection $spellsCast;

    /**
     * @var bool To prevent chain pulls from being killed before the original pull, we defer creating the chain pull
     * until the OG pull is killed. Meanwhile, the chain pull is marked as completed and new pulls are created until
     * then instead of new enemies being added to the chain pull every time.
     */
    private bool $isCompleted;

    public function __construct()
    {
        $this->enemiesInCombat = collect();
        $this->enemiesKilled   = collect();
        $this->spellsCast      = collect();

        $this->isCompleted = false;
    }

    /**
     * @param Carbon $timestamp
     * @return float
     */
    public function getAverageHPPercentAt(Carbon $timestamp): float
    {
        $inCombatSum = $this->enemiesInCombat->sum(function (ActivePullEnemy $activePullEnemy) use ($timestamp) {
            return $activePullEnemy->getHPPercentAt($timestamp);
        });

        $totalEnemiesInPull = ($this->enemiesInCombat->count() + $this->enemiesKilled->count());
        if ($totalEnemiesInPull === 0) {
            return 100;
        } else {
            return $inCombatSum / $totalEnemiesInPull;
        }
    }

    /**
     * @return Collection|ActivePullEnemy[]
     */
    public function getEnemiesInCombat(): Collection
    {
        return $this->enemiesInCombat;
    }

    /**
     * @return Collection|ActivePullEnemy[]
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
     * @param ActivePullEnemy $activePullEnemy
     * @return $this
     */
    protected function activePullEnemyKilled(ActivePullEnemy $activePullEnemy): ActivePull
    {
        $this->enemiesInCombat->forget($activePullEnemy->getUniqueId());
        $this->enemiesKilled->put($activePullEnemy->getUniqueId(), $activePullEnemy);

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
     * @param ActivePullEnemy $activePullEnemy
     * @return $this
     */
    protected function activePullEnemyEngaged(ActivePullEnemy $activePullEnemy): ActivePull
    {
        $this->enemiesInCombat->put($activePullEnemy->getUniqueId(), $activePullEnemy);

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
