<?php

namespace App\Service\LiveSession;

use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionEnemyStateType;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use Illuminate\Support\Collection;

class LiveSessionOverpullDetectionService implements LiveSessionOverpullDetectionServiceInterface
{
    public function __construct(
        private readonly LiveSessionCombatStateServiceInterface $combatStateService,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function processResolvedKills(LiveSession $liveSession, Collection $resolvedKillsInOrder, Collection $inCombatEnemies): void
    {
        /** @var Collection<int, KillZone> $killZones */
        $killZones = KillZone::where('dungeon_route_id', $liveSession->dungeon_route_id)
            ->with('enemies')
            ->orderBy('index')
            ->get();

        /** @var KillZone|null $firstKillZone */
        $firstKillZone = $killZones->first();

        // Map enemy_id → KillZone for fast on-route membership lookup. Kill zones are walked in index order,
        // so when an enemy somehow belongs to more than one, the highest-index (furthest-progress) zone wins
        // - keeping the derived "current pull" deterministic regardless of DB row ordering.
        /** @var Collection<int, KillZone> $enemyIdToKillZone */
        $enemyIdToKillZone = collect();
        foreach ($killZones as $killZone) {
            foreach ($killZone->enemies as $enemy) {
                $enemyIdToKillZone->put($enemy->id, $killZone);
            }
        }

        // Persist the in-combat set first - this must happen before the off-route attribution loop because
        // processOffRouteKill() uses firstOrCreate(), which freezes kill_zone_id on the pass an overpull
        // first appears. The current pull is derived from this set, so it has to be current by then.
        $this->combatStateService->replaceEnemies($liveSession, LiveSessionEnemyStateType::InCombat, $inCombatEnemies);

        if ($resolvedKillsInOrder->isEmpty()) {
            return;
        }

        // Seed the current pull from the live state instead of starting null: the leading edge is the
        // highest-index kill zone we are currently in combat with, falling back to the highest-index kill
        // zone we have already killed in, and finally the first kill zone. This keeps off-route attribution
        // correct across buffer passes even when a chunk contains no on-route kill to anchor it.
        $currentPullKillZone = $this->determineCurrentPullKillZone($liveSession, $inCombatEnemies, $enemyIdToKillZone)
            ?? $firstKillZone;

        $overpullDismissedEnemyIds = $this->combatStateService->getEnemyIds($liveSession, LiveSessionEnemyStateType::OverpullDismissed)->flip();

        foreach ($resolvedKillsInOrder as $enemy) {
            if ($enemyIdToKillZone->has($enemy->id)) {
                $this->combatStateService->setKilledEnemy($liveSession, $enemy->npc_id, $enemy->mdt_id);
                $currentPullKillZone = $enemyIdToKillZone->get($enemy->id);
            } else {
                $attributionKz = $currentPullKillZone ?? $firstKillZone;
                if ($attributionKz === null || $overpullDismissedEnemyIds->has($enemy->id)) {
                    continue;
                }

                $this->processOffRouteKill($liveSession, $enemy, $attributionKz);
            }
        }
    }

    /**
     * The current pull is the highest-index kill zone among the on-route enemies we are currently in combat
     * with; if nothing on-route is engaged, fall back to the highest-index kill zone we have already killed
     * in. Returns null when neither yields a kill zone (the caller then defaults to the first kill zone).
     *
     * @param Collection<int, Enemy>    $inCombatEnemies
     * @param Collection<int, KillZone> $enemyIdToKillZone
     */
    private function determineCurrentPullKillZone(
        LiveSession $liveSession,
        Collection  $inCombatEnemies,
        Collection  $enemyIdToKillZone,
    ): ?KillZone {
        $inCombatKillZone = $this->highestIndexKillZone(
            $inCombatEnemies->map(static fn(Enemy $enemy): int => (int)$enemy->id),
            $enemyIdToKillZone,
        );

        if ($inCombatKillZone !== null) {
            return $inCombatKillZone;
        }

        return $this->highestIndexKillZone(
            $this->combatStateService->getEnemyIds($liveSession, LiveSessionEnemyStateType::Killed),
            $enemyIdToKillZone,
        );
    }

    /**
     * @param Collection<int, int>      $enemyIds
     * @param Collection<int, KillZone> $enemyIdToKillZone
     */
    private function highestIndexKillZone(Collection $enemyIds, Collection $enemyIdToKillZone): ?KillZone
    {
        return $enemyIds
            ->map(static fn(int $enemyId): ?KillZone => $enemyIdToKillZone->get($enemyId))
            ->filter()
            ->sortByDesc('index')
            ->first();
    }

    private function processOffRouteKill(LiveSession $liveSession, Enemy $enemy, KillZone $attributionKz): void
    {
        LiveSessionOverpulledEnemy::query()->firstOrCreate(
            [
                'live_session_id' => $liveSession->id,
                'npc_id'          => $enemy->npc_id,
                'mdt_id'          => $enemy->mdt_id,
            ],
            ['kill_zone_id' => $attributionKz->id],
        );
    }
}
