<?php

namespace App\Logic\DungeonRoute;

use App\Models\Enemy;
use Closure;
use Illuminate\Support\Collection;

/**
 * Plans the pulls of a generated test route floor by floor: every floor gets an equal share of the target
 * enemy forces (a floor too small for it gives what it has, the other floors split the rest), and whatever a
 * floor falls short of its share is owed by the next one. A floor is only left once the route has caught up
 * with its share or the floor has nothing left to pull, so a route walks through the dungeon floor after floor.
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

        $floorShares      = $this->getFloorShares($candidatesByFloor, $targetForces);
        $cumulativeTarget = 0;
        $leftovers        = collect();
        foreach ($candidatesByFloor as $floorId => $floorCandidates) {
            // Measured against the running total, so a floor owes whatever the floors before it fell short
            $cumulativeTarget += $floorShares->get($floorId);

            $leftovers = $leftovers->concat(
                $this->pullUntil($this->shuffleCandidates($floorCandidates), fn() => $this->forces >= $cumulativeTarget),
            );
            $this->pullBosses($floorCandidates);
        }

        // Only reached when the floors ran dry, or when enemies on different floors share their forces
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
     * Splits the target evenly over the floors; a floor that cannot deliver its share in full gives what it has
     * and the rest is split over the other floors, so a small last floor does not leave the route short.
     *
     * @param  Collection<int, Collection<int, Collection<int, Enemy>>> $candidatesByFloor
     * @return Collection<int, float>                                   the forces each floor should deliver, by floor id
     */
    private function getFloorShares(Collection $candidatesByFloor, int $targetForces): Collection
    {
        $capacityByFloorId = $candidatesByFloor->map(fn(Collection $floorCandidates) => $floorCandidates->flatten(1)
            ->map(static fn(Enemy $enemy) => self::getEnemyKey($enemy))
            ->filter()
            ->unique()
            ->sum(fn(string $key) => $this->enemyForcesByKey->get($key, 0)));

        $shares          = collect();
        $remainingForces = $targetForces;
        $floorsLeft      = $capacityByFloorId->count();
        foreach ($capacityByFloorId->sort() as $floorId => $capacity) {
            $share = min($capacity, $remainingForces / $floorsLeft--);
            $shares->put($floorId, $share);
            $remainingForces -= $share;
        }

        return $shares;
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
     * The forces each kill zone enemy key adds to a route, computed the way DungeonRoute::getEnemyForces() does
     * for a route without teeming or shrouded: the rounded average forces of the enemies sharing the key.
     *
     * @param  Collection<int, Enemy>  $enemies               the enemies of one mapping version
     * @param  Collection<int, int>    $npcEnemyForcesByNpcId the mapping version's enemy forces, by npc id
     * @return Collection<string, int>
     */
    public static function getEnemyForcesByKey(Collection $enemies, Collection $npcEnemyForcesByNpcId): Collection
    {
        return $enemies
            ->filter(static fn(Enemy $enemy) => self::getEnemyKey($enemy) !== null)
            ->groupBy(static fn(Enemy $enemy) => self::getEnemyKey($enemy))
            ->map(static fn(Collection $sharingKey) => (int)round($sharingKey->avg(
                static fn(Enemy $enemy) => (int)($enemy->enemy_forces_override ?? $npcEnemyForcesByNpcId->get($enemy->mdt_npc_id ?? $enemy->npc_id, 0)),
            )));
    }

    /**
     * The key DungeonRoute::getEnemyForces() groups forces by; a route counts each key once.
     */
    public static function getEnemyKey(Enemy $enemy): ?string
    {
        $npcId = $enemy->mdt_npc_id ?? $enemy->npc_id;

        return $npcId === null || $enemy->mdt_id === null ? null : sprintf('%d-%d', $npcId, $enemy->mdt_id);
    }
}
