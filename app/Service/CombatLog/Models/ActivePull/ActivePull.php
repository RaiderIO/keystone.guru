<?php

namespace App\Service\CombatLog\Models\ActivePull;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class ActivePull
{
    /** @var Collection|ActivePullEnemy[] */
    protected Collection $enemiesInCombat;

    /** @var Collection|ActivePullEnemy[] */
    protected Collection $enemiesKilled;

    protected Collection $spellsCast;

    /**
     * @var bool To prevent chain pulls from being killed before the original pull, we defer creating the chain pull
     *           until the OG pull is killed. Meanwhile, the chain pull is marked as completed and new pulls are created until
     *           then instead of new enemies being added to the chain pull every time.
     */
    private bool $isCompleted;

    public function __construct()
    {
        $this->enemiesInCombat = collect();
        $this->enemiesKilled = collect();
        $this->spellsCast = collect();

        $this->isCompleted = false;
    }

    public function getAverageHPPercentAt(Carbon $timestamp): float
    {
        $inCombatSum = $this->enemiesInCombat->sum(fn (ActivePullEnemy $activePullEnemy) => $activePullEnemy->getHPPercentAt($timestamp));

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

    public function getSpellsCast(): Collection
    {
        return $this->spellsCast;
    }

    /**
     * @return $this
     */
    public function enemyKilled(string $uniqueId): ActivePull
    {
        $activePullEnemy = $this->enemiesInCombat->get($uniqueId);
        if ($activePullEnemy !== null) {
            $this->enemiesInCombat->forget($uniqueId);
            $this->enemiesKilled->put($activePullEnemy->getUniqueId(), $activePullEnemy);

            if ($this->enemiesInCombat->isEmpty()) {
                $this->isCompleted = true;
            }
        }

        return $this;
    }

    /**
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
     * @return $this
     */
    public function enemyEngaged(ActivePullEnemy $activePullEnemy): ActivePull
    {
        $this->enemiesInCombat->put($activePullEnemy->getUniqueId(), $activePullEnemy);

        return $this;
    }

    public function isEnemyInCombat(string $uniqueUid): bool
    {
        return $this->enemiesInCombat->has($uniqueUid);
    }

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function merge(ActivePull $activePull): void
    {
        $this->enemiesInCombat = $this->enemiesInCombat->merge($activePull->enemiesInCombat);
        $this->enemiesKilled = $this->enemiesKilled->merge($activePull->enemiesKilled);
    }

    public function getAvgLatLng(): array
    {
        $result = ['lat' => 0, 'lng' => 0];

        $count = 0;
        foreach ($this->enemiesKilled as $killedActivePullEnemy) {
            if ($killedActivePullEnemy->getResolvedEnemy() === null) {
                continue;
            }

            $result['lat'] += $killedActivePullEnemy->getResolvedEnemy()->lat;
            $result['lng'] += $killedActivePullEnemy->getResolvedEnemy()->lng;
            $count++;
        }

        $result['lat'] /= $count;
        $result['lng'] /= $count;

        return $result;
    }
}
