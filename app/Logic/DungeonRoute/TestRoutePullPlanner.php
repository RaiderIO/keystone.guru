<?php

namespace App\Logic\DungeonRoute;

use App\Models\Enemy;
use Closure;
use Illuminate\Support\Collection;

/**
 * Plans the pulls of a generated test route floor by floor: every floor gets an equal share of the target
 * enemy forces, and whatever a floor could not deliver is owed by the next one. A floor is only left once
 * its share is met or it has nothing left to pull, so a route walks through the dungeon floor after floor.
 */
class TestRoutePullPlanner
{
    private const int SKIP_PULL_CANDIDATE_PERCENTAGE = 25;

    /** Packs average about three enemies; real routes pull closer to five at a time. */
    private const int PULL_SIZE_MIN = 4;

    private const int PULL_SIZE_MAX = 7;

    /** @var Collection<int, Collection<int, Enemy>> */
    private Collection $pulls;

    /** @var array<int, true> */
    private array $pulledEnemyIds = [];

    /** @var array<string, true> */
    private array $killedKeys = [];

    private int $forces = 0;

    /**
     * @param Collection<string, int> $enemyForcesByKey the forces a kill zone enemy key adds to a route
     */
    public function __construct(
        private readonly Collection $enemyForcesByKey,
    ) {
        $this->pulls = collect();
    }

    /**
     * @param  Collection<int, Collection<int, Enemy>> $pullCandidates      packs and loose enemies, each on a single floor
     * @param  Collection<int, int>                    $floorIndexByFloorId the order in which the floors are visited
     * @return Collection<int, Collection<int, Enemy>> the pulls in route order
     */
    public function plan(Collection $pullCandidates, Collection $floorIndexByFloorId, int $targetForces): Collection
    {
        $this->pulls          = collect();
        $this->pulledEnemyIds = [];
        $this->killedKeys     = [];
        $this->forces         = 0;

        $candidatesByFloor = $pullCandidates
            ->groupBy(static fn(Collection $enemies) => $enemies->first()->floor_id)
            ->sortKeys()
            ->sortBy(static fn(Collection $floorCandidates, int $floorId) => $floorIndexByFloorId->get($floorId, PHP_INT_MAX));

        $floorBudget = $targetForces / max(1, $candidatesByFloor->count());
        $owedForces  = 0;
        $leftovers   = collect();
        foreach ($candidatesByFloor as $floorCandidates) {
            $budget           = $floorBudget + $owedForces;
            $floorStartForces = $this->forces;

            $leftovers = $leftovers->concat(
                $this->pullUntil($this->shuffleCandidates($floorCandidates), fn() => $this->forces - $floorStartForces >= $budget),
            );
            $this->pullBosses($floorCandidates);

            $owedForces = max(0, $budget - ($this->forces - $floorStartForces));
        }

        // Only reached when the last floor runs dry before the route is complete
        if ($this->forces < $targetForces) {
            $this->pullUntil($leftovers, fn() => $this->forces >= $targetForces);
        }

        return $this->pulls;
    }

    public function getForces(): int
    {
        return $this->forces;
    }

    /**
     * Skipping a random part of the candidates on the first pass keeps routes of the same dungeon apart.
     *
     * @param  Collection<int, Collection<int, Enemy>> $candidates
     * @return Collection<int, Collection<int, Enemy>>
     */
    private function shuffleCandidates(Collection $candidates): Collection
    {
        [$skipped, $firstPass] = $candidates->partition(static fn() => random_int(1, 100) <= self::SKIP_PULL_CANDIDATE_PERCENTAGE);

        return $firstPass->concat($skipped)->values();
    }

    /**
     * Merges consecutive candidates on the same floor into pulls of a random minimum size until $isDone holds.
     *
     * @param  Collection<int, Collection<int, Enemy>> $candidates
     * @param  Closure(): bool                         $isDone
     * @return Collection<int, Collection<int, Enemy>> the candidates that were not pulled
     */
    private function pullUntil(Collection $candidates, Closure $isDone): Collection
    {
        $candidates = $candidates->values();
        $pull       = collect();
        $pullSize   = random_int(self::PULL_SIZE_MIN, self::PULL_SIZE_MAX);

        foreach ($candidates as $index => $enemies) {
            if ($isDone()) {
                return $candidates->slice($index)->values();
            }

            $enemies = $enemies->reject(fn(Enemy $enemy) => isset($this->pulledEnemyIds[$enemy->id]))->values();
            if ($enemies->isEmpty()) {
                continue;
            }

            if ($pull->isNotEmpty() && $pull->first()->floor_id !== $enemies->first()->floor_id) {
                $this->addPull($pull);
                $pull     = collect();
                $pullSize = random_int(self::PULL_SIZE_MIN, self::PULL_SIZE_MAX);
            }

            $pull = $pull->concat($enemies);

            if ($pull->count() >= $pullSize) {
                $this->addPull($pull);
                $pull     = collect();
                $pullSize = random_int(self::PULL_SIZE_MIN, self::PULL_SIZE_MAX);
            }
        }

        if ($pull->isNotEmpty()) {
            $this->addPull($pull);
        }

        return collect();
    }

    /**
     * @param Collection<int, Collection<int, Enemy>> $floorCandidates
     */
    private function pullBosses(Collection $floorCandidates): void
    {
        $bosses = $floorCandidates->flatten(1)
            ->filter(fn(Enemy $enemy) => $enemy->npc?->isBoss() && !isset($this->pulledEnemyIds[$enemy->id]));

        foreach ($bosses as $boss) {
            $this->addPull(collect([$boss]));
        }
    }

    /**
     * @param Collection<int, Enemy> $enemies
     */
    private function addPull(Collection $enemies): void
    {
        $this->pulls->push($enemies);

        foreach ($enemies as $enemy) {
            $this->pulledEnemyIds[$enemy->id] = true;

            $key = self::getEnemyKey($enemy);
            if ($key !== null && !isset($this->killedKeys[$key])) {
                $this->killedKeys[$key] = true;
                $this->forces += $this->enemyForcesByKey->get($key, 0);
            }
        }
    }

    /**
     * The key DungeonRoute::getEnemyForces() counts forces by: every enemy sharing it counts, once per route.
     */
    public static function getEnemyKey(Enemy $enemy): ?string
    {
        $npcId = $enemy->mdt_npc_id ?? $enemy->npc_id;

        return $npcId === null || $enemy->mdt_id === null ? null : sprintf('%d-%d', $npcId, $enemy->mdt_id);
    }
}
