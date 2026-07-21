<?php

namespace App\Service\LiveSession;

use App\Events\LiveSession\InCombatEnemiesChangedEvent;
use App\Events\LiveSession\OverpulledEnemy\OverpulledEnemyChangedEvent;
use App\Events\LiveSession\RouteCorrectionEvent;
use App\Events\Models\LiveSession\EnemyKilledEvent;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;

class LiveSessionOverpullDetectionService implements LiveSessionOverpullDetectionServiceInterface
{
    public function __construct(
        private readonly LiveSessionCombatStateServiceInterface $combatStateService,
        private readonly OverpulledEnemyServiceInterface        $overpulledEnemyService,
        private readonly CoordinatesServiceInterface            $coordinatesService,
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
        $this->persistAndBroadcastInCombat($liveSession, $inCombatEnemies);

        if ($resolvedKillsInOrder->isEmpty()) {
            $this->recomputeObsoleteIfNeeded($liveSession);

            return;
        }

        // Seed the current pull from the live state instead of starting null: the leading edge is the
        // highest-index kill zone we are currently in combat with, falling back to the highest-index kill
        // zone we have already killed in, and finally the first kill zone. This keeps off-route attribution
        // correct across buffer passes even when a chunk contains no on-route kill to anchor it.
        $currentPullKillZone = $this->determineCurrentPullKillZone($liveSession, $inCombatEnemies, $enemyIdToKillZone)
            ?? $firstKillZone;

        foreach ($resolvedKillsInOrder as $enemy) {
            if ($enemyIdToKillZone->has($enemy->id)) {
                $this->processOnRouteKill($liveSession, $enemy);
                $currentPullKillZone = $enemyIdToKillZone->get($enemy->id);
            } else {
                $attributionKz = $currentPullKillZone ?? $firstKillZone;
                if ($attributionKz === null) {
                    continue;
                }

                $this->processOffRouteKill($liveSession, $enemy, $attributionKz);
            }
        }

        $this->recomputeObsoleteIfNeeded($liveSession);
    }

    /**
     * Persist the full current in-combat enemy set and broadcast it to clients only when it changed
     * (avoids spamming an identical payload every buffer pass).
     *
     * @param Collection<int, Enemy> $inCombatEnemies
     */
    private function persistAndBroadcastInCombat(LiveSession $liveSession, Collection $inCombatEnemies): void
    {
        $previousInCombatEnemyIds = $this->combatStateService->getInCombatEnemyIds($liveSession)
            ->map(static fn($id): int => (int)$id)
            ->unique()
            ->sort()
            ->values();

        $this->combatStateService->replaceInCombatEnemies($liveSession, $inCombatEnemies);

        $currentInCombatEnemyIds = $inCombatEnemies
            ->map(static fn(Enemy $enemy): int => (int)$enemy->id)
            ->unique()
            ->sort()
            ->values();

        if ($currentInCombatEnemyIds->all() !== $previousInCombatEnemyIds->all()) {
            broadcast(new InCombatEnemiesChangedEvent($liveSession, $liveSession->user, $currentInCombatEnemyIds->all()));
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
            $this->combatStateService->getKilledEnemyIds($liveSession),
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

    private function recomputeObsoleteIfNeeded(LiveSession $liveSession): void
    {
        // The obsolete set is dynamic: it must be re-derived on every chunk, not just when a new overpull
        // appears. Killing an enemy that was previously marked obsolete is an on-route kill (no new
        // overpull), yet it must drop out of the obsolete set and have a replacement marked further down.
        // We only recompute when overpulls exist (the only thing that produces obsolete enemies) or when
        // stale obsolete rows are still present that may need clearing.
        if ($liveSession->overpulledEnemies()->exists() || $liveSession->obsoleteEnemies()->exists()) {
            $this->recomputeAndBroadcastObsolete($liveSession);
        }
    }

    private function processOnRouteKill(LiveSession $liveSession, Enemy $enemy): void
    {
        $isNew = $this->combatStateService->setKilledEnemy($liveSession, $enemy->npc_id, $enemy->mdt_id);
        if ($isNew) {
            broadcast(new EnemyKilledEvent($this->coordinatesService, $liveSession, $liveSession->user, $enemy));
        }
    }

    private function processOffRouteKill(LiveSession $liveSession, Enemy $enemy, KillZone $attributionKz): void
    {
        $overpulled = LiveSessionOverpulledEnemy::query()->firstOrCreate(
            [
                'live_session_id' => $liveSession->id,
                'npc_id'          => $enemy->npc_id,
                'mdt_id'          => $enemy->mdt_id,
            ],
            ['kill_zone_id' => $attributionKz->id],
        );

        if ($overpulled->wasRecentlyCreated) {
            broadcast(new OverpulledEnemyChangedEvent($liveSession, $liveSession->user, $overpulled, $enemy));
        }
    }

    private function recomputeAndBroadcastObsolete(LiveSession $liveSession): void
    {
        $correction = $this->overpulledEnemyService->getRouteCorrection($liveSession);

        // Cast to int: getObsoleteEnemies() reads enemy_id off a raw DB::select (PDO may hand it back as a
        // string), while getObsoleteEnemyIds() is an Eloquent int. Normalising keeps the diff honest and
        // ensures the broadcast payload matches the integer enemy ids the frontend compares against.
        $obsoleteEnemyIds = $correction->getObsoleteEnemies()
            ->map(static fn($id): int => (int)$id)
            ->unique()
            ->sort()
            ->values();

        // This method runs on every chunk now, so only touch the DB and broadcast when the obsolete set
        // actually changed - otherwise we would spam RouteCorrectionEvent every batch with identical data.
        $currentObsoleteEnemyIds = $this->combatStateService->getObsoleteEnemyIds($liveSession)
            ->map(static fn($id): int => (int)$id)
            ->sort()
            ->values();

        if ($obsoleteEnemyIds->all() === $currentObsoleteEnemyIds->all()) {
            return;
        }

        $pairs = Enemy::whereIn('id', $obsoleteEnemyIds)
            ->get(['id', 'npc_id', 'mdt_id'])
            ->map(static fn(Enemy $e) => ['npc_id' => $e->npc_id, 'mdt_id' => $e->mdt_id])
            ->toArray();

        $this->combatStateService->replaceObsoleteEnemies($liveSession, $pairs);

        // The frontend treats this as the authoritative full obsolete set (it un-marks any enemy not in
        // the list), so we broadcast the complete current set rather than just the additions.
        broadcast(new RouteCorrectionEvent($liveSession, $liveSession->user, $obsoleteEnemyIds->toArray()));
    }
}
