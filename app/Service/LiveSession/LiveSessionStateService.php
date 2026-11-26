<?php

namespace App\Service\LiveSession;

use App\Events\LiveSession\LiveSessionStateChangedEvent;
use App\Models\Enemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionEnemyStateType;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\LiveSession\Dtos\LiveSessionStateBroadcastResult;
use App\Service\LiveSession\Logging\LiveSessionStateServiceLoggingInterface;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Throwable;

/**
 * @phpstan-import-type LiveSessionState from LiveSessionStateServiceInterface
 * @phpstan-import-type LiveSessionStatePlayer from LiveSessionStateServiceInterface
 */
class LiveSessionStateService implements LiveSessionStateServiceInterface
{
    /**
     * Two decimals is a third of a pixel at the map's highest zoom, and keeps jitter out of the state hash.
     */
    private const int COORDINATE_PRECISION = 2;

    /**
     * Outlives any live session; once it lapses the next pass simply broadcasts once more.
     */
    private const int BROADCAST_HASH_TTL_SECONDS = 86400;

    public function __construct(
        private readonly LiveSessionCombatStateServiceInterface  $combatStateService,
        private readonly OverpulledEnemyServiceInterface         $overpulledEnemyService,
        private readonly CoordinatesServiceInterface             $coordinatesService,
        private readonly CacheServiceInterface                   $cacheService,
        private readonly LiveSessionStateServiceLoggingInterface $log,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getState(LiveSession $liveSession): array
    {
        return [
            'killed'     => self::encodeEnemyIds($this->combatStateService->getEnemyIds($liveSession, LiveSessionEnemyStateType::Killed)),
            'in_combat'  => self::encodeEnemyIds($this->combatStateService->getEnemyIds($liveSession, LiveSessionEnemyStateType::InCombat)),
            'obsolete'   => self::encodeEnemyIds($this->overpulledEnemyService->getRouteCorrection($liveSession)->getObsoleteEnemies()),
            'overpulled' => $this->getOverpulledEnemies($liveSession),
            'players'    => $this->getPlayers($liveSession),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function broadcastState(LiveSession $liveSession, User $user): LiveSessionStateBroadcastResult
    {
        return $this->withBroadcastLock($liveSession, function () use ($liveSession, $user): LiveSessionStateBroadcastResult {
            $state = $this->getState($liveSession);

            return $this->broadcast($liveSession, $user, $state, self::hashState($state));
        });
    }

    /**
     * {@inheritDoc}
     */
    public function broadcastStateIfChanged(LiveSession $liveSession, User $user): LiveSessionStateBroadcastResult
    {
        return $this->withBroadcastLock($liveSession, function () use ($liveSession, $user): LiveSessionStateBroadcastResult {
            $state = $this->getState($liveSession);
            $hash  = self::hashState($state);

            if ($this->cacheService->get(self::getBroadcastHashCacheKey($liveSession)) === $hash) {
                $this->log->broadcastStateIfChangedUnchanged($liveSession->id, $hash);

                return LiveSessionStateBroadcastResult::Unchanged;
            }

            return $this->broadcast($liveSession, $user, $state, $hash);
        });
    }

    /**
     * Capturing the state inside the lock makes whichever writer publishes last also publish the newest committed
     * state; viewers replace their state with every broadcast, so an older capture published later would undo a newer
     * one.
     *
     * @param Closure(): LiveSessionStateBroadcastResult $callable
     */
    private function withBroadcastLock(LiveSession $liveSession, Closure $callable): LiveSessionStateBroadcastResult
    {
        $waitSeconds = (int)config('keystoneguru.live_sessions.broadcast_lock_wait_seconds');

        try {
            return $this->cacheService->lock(
                sprintf('live-session-state-broadcast-%d', $liveSession->id),
                $callable,
                $waitSeconds,
                (int)config('keystoneguru.live_sessions.broadcast_lock_lease_seconds'),
            );
        } catch (LockTimeoutException) {
            $this->log->withBroadcastLockTimedOut($liveSession->id, $waitSeconds);

            return LiveSessionStateBroadcastResult::Failed;
        }
    }

    /**
     * @param LiveSessionState $state
     */
    private function broadcast(LiveSession $liveSession, User $user, array $state, string $hash): LiveSessionStateBroadcastResult
    {
        try {
            broadcast(new LiveSessionStateChangedEvent($liveSession, $user, $state));
        } catch (Throwable $throwable) {
            // Nothing remembered, so the next pass tries again with whatever the state is by then
            $this->log->broadcastFailed($liveSession->id, $throwable);

            return LiveSessionStateBroadcastResult::Failed;
        }

        $this->cacheService->set(self::getBroadcastHashCacheKey($liveSession), $hash, self::BROADCAST_HASH_TTL_SECONDS);
        $this->log->broadcastSent($liveSession->id, $hash);

        return LiveSessionStateBroadcastResult::Sent;
    }

    /**
     * @return array<int, array{0: int, 1: int}>
     */
    private function getOverpulledEnemies(LiveSession $liveSession): array
    {
        return $liveSession->enemiesMatchingRowsOf('live_session_overpulled_enemies')
            ->select('enemies.id', 'live_session_overpulled_enemies.kill_zone_id')
            ->orderBy('enemies.id')
            ->get()
            ->unique('id')
            ->map(static fn(Enemy $enemy): array => [(int)$enemy->id, (int)$enemy->getAttribute('kill_zone_id')])
            ->values()
            ->all();
    }

    /**
     * @return array<int, LiveSessionStatePlayer>
     */
    private function getPlayers(LiveSession $liveSession): array
    {
        /** @var Collection<int, LiveSessionPlayerPosition> $playerPositions */
        $playerPositions = $liveSession->playerPositions()
            ->with('floor')
            ->orderBy('player_guid')
            ->get();

        $players = [];
        foreach ($playerPositions as $playerPosition) {
            $playerPosition->setRelation('liveSession', $liveSession);

            $coordinates = $playerPosition->getCoordinatesData($this->coordinatesService)['coordinates'] ?? null;
            if ($coordinates === null) {
                continue;
            }

            $players[] = [
                'guid'                    => $playerPosition->player_guid,
                'name'                    => $playerPosition->character_name,
                'class_id'                => $playerPosition->class_id,
                'specialization_id'       => $playerPosition->specialization_id,
                'specialization_icon_url' => $playerPosition->specialization_icon_url,
                'split_floors'            => self::compactCoordinate($coordinates[User::MAP_FACADE_STYLE_SPLIT_FLOORS]),
                'facade'                  => self::compactCoordinate($coordinates[User::MAP_FACADE_STYLE_FACADE]),
            ];
        }

        return $players;
    }

    /**
     * @param array{lat: float, lng: float, floor_id: int|null} $coordinate
     *
     * @return array{0: float, 1: float, 2: int|null}
     */
    private static function compactCoordinate(array $coordinate): array
    {
        return [
            round($coordinate['lat'], self::COORDINATE_PRECISION),
            round($coordinate['lng'], self::COORDINATE_PRECISION),
            $coordinate['floor_id'],
        ];
    }

    /**
     * @param Collection<int, int> $enemyIds
     *
     * @return array<int, int>
     */
    private static function encodeEnemyIds(Collection $enemyIds): array
    {
        $encoded  = [];
        $previous = 0;
        foreach ($enemyIds->map(static fn($enemyId): int => (int)$enemyId)->unique()->sort()->values() as $enemyId) {
            $encoded[] = $enemyId - $previous;
            $previous  = $enemyId;
        }

        return $encoded;
    }

    /**
     * @param LiveSessionState $state
     */
    private static function hashState(array $state): string
    {
        return md5(json_encode($state, JSON_THROW_ON_ERROR));
    }

    private static function getBroadcastHashCacheKey(LiveSession $liveSession): string
    {
        return sprintf('live-session:%d:broadcast-state-hash', $liveSession->id);
    }
}
