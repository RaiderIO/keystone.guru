<?php

namespace App\Service\LiveSession;

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
    public function processResolvedKills(LiveSession $liveSession, Collection $resolvedKillsInOrder): void
    {
        if ($resolvedKillsInOrder->isEmpty()) {
            return;
        }

        $killZones = KillZone::where('dungeon_route_id', $liveSession->dungeon_route_id)
            ->with('enemies')
            ->get();

        /** @var KillZone|null $firstKillZone */
        $firstKillZone = $killZones->sortBy('index')->first();

        // Map enemy_id → KillZone for fast on-route membership lookup
        /** @var Collection<int, KillZone> $enemyIdToKillZone */
        $enemyIdToKillZone = collect();
        foreach ($killZones as $killZone) {
            foreach ($killZone->enemies as $enemy) {
                $enemyIdToKillZone->put($enemy->id, $killZone);
            }
        }

        /** @var KillZone|null $currentPullKillZone */
        $currentPullKillZone = null;

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
