<?php

namespace Tests\Feature\App\Service\LiveSession;

use App\Events\LiveSession\LiveSessionStateChangedEvent;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionEnemyState;
use App\Models\LiveSession\LiveSessionEnemyStateType;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\LiveSession\Dtos\LiveSessionStateBroadcastResult;
use App\Service\LiveSession\DungeonRouteCorrection;
use App\Service\LiveSession\LiveSessionStateServiceInterface;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use App\Service\MapContext\MapContextServiceInterface;
use Carbon\CarbonInterface;
use GuzzleHttp\Psr7\Message;
use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Pusher\Pusher;
use Tests\Feature\Traits\GeneratesDungeonRoutes;
use Tests\Fixtures\Traits\FailsBroadcasts;
use Tests\TestCases\PublicTestCase;

#[Group('LiveSession')]
#[Group('LiveSessionState')]
final class LiveSessionStateServiceTest extends PublicTestCase
{
    use FailsBroadcasts;
    use GeneratesDungeonRoutes;

    private const int PIT_OF_SARON_CHALLENGE_MODE_ID = 556;

    /**
     * The share of Reverb's max_request_size the worst realistic state may take. Plain enemy id lists put that
     * case at almost 90% of the limit; the delta-encoded lists at about half.
     */
    private const float MAX_REQUEST_SIZE_SHARE = 0.6;

    // -------------------------------------------------------------------------
    // getState
    // -------------------------------------------------------------------------

    #[Test]
    public function getState_givenKilledAndInCombatEnemies_returnsTheirIdsDeltaEncoded(): void
    {
        // Arrange
        $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();
        $liveSession  = null;

        try {
            $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);
            $enemies     = $this->uniquelyMatchedEnemies($dungeonRoute->mappingVersion, 3);
            $this->assertCount(3, $enemies, 'The route needs three enemies with a unique npc_id/mdt_id pair');

            foreach ([$enemies[2], $enemies[0], $enemies[1]] as $enemy) {
                $this->storeEnemyState($liveSession, LiveSessionEnemyStateType::Killed, $enemy);
            }
            $this->storeEnemyState($liveSession, LiveSessionEnemyStateType::InCombat, $enemies[1]);

            // Act
            $state = $this->getService()->getState($liveSession);

            // Assert
            $this->assertSame([
                $enemies[0]->id,
                $enemies[1]->id - $enemies[0]->id,
                $enemies[2]->id - $enemies[1]->id,
            ], $state['killed']);
            $this->assertSame([$enemies[1]->id], $state['in_combat']);
        } finally {
            $this->cleanupLiveSession($liveSession);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getState_givenOverpulledEnemies_returnsEnemyAndKillZonePairsSortedByEnemy(): void
    {
        // Arrange
        $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();
        $liveSession  = null;

        try {
            $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);
            $enemies     = $this->uniquelyMatchedEnemies($dungeonRoute->mappingVersion, 2);
            $this->assertCount(2, $enemies, 'The route needs two enemies with a unique npc_id/mdt_id pair');

            $this->storeOverpulledEnemy($liveSession, $enemies[1], 4243);
            $this->storeOverpulledEnemy($liveSession, $enemies[0], 4242);

            // Act
            $state = $this->getService()->getState($liveSession);

            // Assert
            $this->assertSame([
                [$enemies[0]->id, 4242],
                [$enemies[1]->id, 4243],
            ], $state['overpulled']);
        } finally {
            $this->cleanupLiveSession($liveSession);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getState_givenARouteCorrection_returnsItsObsoleteEnemiesOnceEachSortedAndDeltaEncoded(): void
    {
        // Arrange
        $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();
        $liveSession  = null;

        try {
            $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);
            $this->mockRouteCorrection($liveSession, [1300, 1200, 1300]);

            // Act
            $state = $this->getService()->getState($liveSession);

            // Assert
            $this->assertSame([1200, 100], $state['obsolete']);
        } finally {
            $this->cleanupLiveSession($liveSession);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getState_givenAPlayerOnAFacadeDungeon_returnsTheSplitFloorAndTheFacadeCoordinates(): void
    {
        // Arrange
        $dungeon        = Dungeon::query()->where('challenge_mode_id', self::PIT_OF_SARON_CHALLENGE_MODE_ID)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertTrue($dungeon->floors()->where('facade', 1)->exists(), 'Pit of Saron needs a facade floor');

        /** @var Enemy $enemy */
        $enemy = Enemy::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->whereHas('floor', static fn($query) => $query->where('facade', 0))
            ->with('floor')
            ->firstOrFail();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);
        $liveSession = null;

        try {
            $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);
            LiveSessionPlayerPosition::factory()->withSpecialization(7, 262)->create([
                'live_session_id' => $liveSession->id,
                'player_guid'     => 'Player-1084-0A6BB627',
                'character_name'  => 'Herochar',
                'lat'             => $enemy->lat,
                'lng'             => $enemy->lng,
                'floor_id'        => $enemy->floor_id,
            ]);

            $expectedFacade = app(CoordinatesServiceInterface::class)->convertMapLocationToFacadeMapLocation(
                $mappingVersion,
                new LatLng($enemy->lat, $enemy->lng, $enemy->floor),
            );

            // Act
            $state = $this->getService()->getState($liveSession);

            // Assert
            $this->assertCount(1, $state['players']);
            $player = $state['players'][0];
            $this->assertSame('Player-1084-0A6BB627', $player['guid']);
            $this->assertSame('Herochar', $player['name']);
            $this->assertSame(7, $player['class_id']);
            $this->assertSame(262, $player['specialization_id']);
            $this->assertNotNull($player['specialization_icon_url']);
            $this->assertSame([round($enemy->lat, 2), round($enemy->lng, 2), $enemy->floor_id], $player['split_floors']);
            $this->assertSame(
                [round($expectedFacade->getLat(), 2), round($expectedFacade->getLng(), 2), $expectedFacade->getFloor()->id],
                $player['facade'],
            );
            $this->assertTrue((bool)Floor::findOrFail($player['facade'][2])->facade);
        } finally {
            $this->cleanupLiveSession($liveSession);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getState_givenAnOverpullDismissedEnemy_leavesItOutOfTheState(): void
    {
        // Arrange
        $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();
        $liveSession  = null;

        try {
            $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);
            $enemies     = $this->uniquelyMatchedEnemies($dungeonRoute->mappingVersion, 3);
            $this->assertCount(3, $enemies, 'The route needs three enemies with a unique npc_id/mdt_id pair');

            [$killedEnemy, $inCombatEnemy, $dismissedEnemy] = $enemies->all();
            $this->storeEnemyState($liveSession, LiveSessionEnemyStateType::Killed, $killedEnemy);
            $this->storeEnemyState($liveSession, LiveSessionEnemyStateType::InCombat, $inCombatEnemy);
            $this->storeEnemyState($liveSession, LiveSessionEnemyStateType::OverpullDismissed, $dismissedEnemy);

            // Act
            $state = $this->getService()->getState($liveSession);

            // Assert
            $this->assertSame([
                'killed'     => [$killedEnemy->id],
                'in_combat'  => [$inCombatEnemy->id],
                'obsolete'   => [],
                'overpulled' => [],
                'players'    => [],
            ], $state);
        } finally {
            $this->cleanupLiveSession($liveSession);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getState_givenNothingRecorded_returnsEmptyLists(): void
    {
        // Arrange
        $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();
        $liveSession  = null;

        try {
            $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);

            // Act
            $state = $this->getService()->getState($liveSession);

            // Assert
            $this->assertSame([
                'killed'     => [],
                'in_combat'  => [],
                'obsolete'   => [],
                'overpulled' => [],
                'players'    => [],
            ], $state);
        } finally {
            $this->cleanupLiveSession($liveSession);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getState_givenTheWorstRealisticSession_fitsWellWithinReverbsRequestLimit(): void
    {
        // Arrange - every enemy of the seeded mapping version with the most enemies killed, a few dozen overpulled
        // and obsolete spread over the whole id range, and a full group of five players
        /** @var MappingVersion $mappingVersion */
        $mappingVersion = MappingVersion::query()
            ->whereKey(Enemy::query()->selectRaw('mapping_version_id')->groupBy('mapping_version_id')->orderByRaw('count(*) desc')->limit(1))
            ->firstOrFail();
        $enemies = Enemy::query()->where('mapping_version_id', $mappingVersion->id)->orderBy('id')->get();
        /** @var Floor $floor */
        $floor = $mappingVersion->dungeon->floors()->firstOrFail();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
        ]);
        $liveSession = null;

        try {
            $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);

            LiveSessionEnemyState::query()->insertOrIgnore($enemies->map(static fn(Enemy $enemy): array => [
                'live_session_id' => $liveSession->id,
                'state'           => LiveSessionEnemyStateType::Killed->value,
                'npc_id'          => $enemy->npc_id,
                'mdt_id'          => $enemy->mdt_id,
            ])->all());
            foreach ($enemies->take(10) as $enemy) {
                $this->storeEnemyState($liveSession, LiveSessionEnemyStateType::InCombat, $enemy);
            }
            foreach ($enemies->nth(25, 3)->unique(static fn(Enemy $enemy): string => sprintf('%d-%d', $enemy->npc_id, $enemy->mdt_id))->take(36) as $enemy) {
                $this->storeOverpulledEnemy($liveSession, $enemy, 99999999);
            }
            $this->mockRouteCorrection($liveSession, $enemies->nth(25, 7)->take(36)->pluck('id')->all());
            for ($i = 0; $i < 5; $i++) {
                LiveSessionPlayerPosition::factory()->withSpecialization(6, 251)->create([
                    'live_session_id' => $liveSession->id,
                    'player_guid'     => sprintf('Player-1084-0A6BB62%d', $i),
                    'character_name'  => sprintf('Charactername%d-Argentdawn-EU', $i),
                    'lat'             => -123.456789,
                    'lng'             => 234.567891,
                    'floor_id'        => $floor->id,
                ]);
            }

            $state = $this->getService()->getState($liveSession);
            $this->assertCount($enemies->count(), $state['killed']);
            $this->assertCount(5, $state['players']);

            $event = new LiveSessionStateChangedEvent($liveSession, $liveSession->user, $state);

            // Act
            $requestSize = $this->getPublishRequestSize($event);

            // Assert
            $this->assertLessThanOrEqual(
                (int)(config('reverb.servers.reverb.max_request_size') * self::MAX_REQUEST_SIZE_SHARE),
                $requestSize,
                sprintf('Publishing the state of %d killed enemies takes %d bytes', $enemies->count(), $requestSize),
            );
        } finally {
            $this->cleanupLiveSession($liveSession);
            $dungeonRoute->delete();
        }
    }

    // -------------------------------------------------------------------------
    // Broadcasting
    // -------------------------------------------------------------------------

    #[Test]
    public function broadcastState_givenASession_broadcastsTheStateTheMapContextLoads(): void
    {
        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();
        $liveSession  = null;

        try {
            $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);
            $enemies     = $this->uniquelyMatchedEnemies($dungeonRoute->mappingVersion, 3);
            $this->storeEnemyState($liveSession, LiveSessionEnemyStateType::Killed, $enemies[0]);
            $this->storeEnemyState($liveSession, LiveSessionEnemyStateType::InCombat, $enemies[1]);
            $this->storeOverpulledEnemy($liveSession, $enemies[2], 4242);
            LiveSessionPlayerPosition::factory()->create([
                'live_session_id' => $liveSession->id,
                'lat'             => $enemies[0]->lat,
                'lng'             => $enemies[0]->lng,
                'floor_id'        => $enemies[0]->floor_id,
            ]);

            $mapContext = app(MapContextServiceInterface::class)
                ->createMapContextLiveSession($liveSession, User::MAP_FACADE_STYLE_SPLIT_FLOORS);
            $mapContextState = $mapContext->toArray()['liveSessionState'];

            // Act
            $broadcastResult = $this->getService()->broadcastState($liveSession, $liveSession->user);

            // Assert
            $this->assertSame(LiveSessionStateBroadcastResult::Sent, $broadcastResult);
            $this->assertNotEmpty($mapContextState['killed']);
            $this->assertNotEmpty($mapContextState['players']);
            Event::assertDispatched(
                LiveSessionStateChangedEvent::class,
                static fn(LiveSessionStateChangedEvent $event): bool => $event->broadcastWith()['state'] === $mapContextState
                    && $event->broadcastOn()[0]->name === sprintf('presence-%s', $mapContext->getEchoChannelName()),
            );
        } finally {
            $this->cleanupLiveSession($liveSession);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function broadcastState_givenAnUnchangedState_broadcastsEveryTime(): void
    {
        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $this->getService()->broadcastState($liveSession, $liveSession->user);
            $this->getService()->broadcastState($liveSession, $liveSession->user);

            // Assert
            Event::assertDispatchedTimes(LiveSessionStateChangedEvent::class, 2);
        } finally {
            $dungeonRoute = $liveSession->dungeonRoute;
            $this->cleanupLiveSession($liveSession);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function broadcastStateIfChanged_givenAnUnchangedState_broadcastsItOnlyOnce(): void
    {
        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $firstBroadcastResult  = $this->getService()->broadcastStateIfChanged($liveSession, $liveSession->user);
            $secondBroadcastResult = $this->getService()->broadcastStateIfChanged($liveSession, $liveSession->user);

            // Assert
            $this->assertSame(LiveSessionStateBroadcastResult::Sent, $firstBroadcastResult);
            $this->assertSame(LiveSessionStateBroadcastResult::Unchanged, $secondBroadcastResult);
            Event::assertDispatchedTimes(LiveSessionStateChangedEvent::class, 1);
        } finally {
            $dungeonRoute = $liveSession->dungeonRoute;
            $this->cleanupLiveSession($liveSession);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function broadcastStateIfChanged_givenTheStateChangedSinceTheLastBroadcast_broadcastsItAgain(): void
    {
        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();
        $liveSession  = null;

        try {
            $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);
            $enemy       = $this->uniquelyMatchedEnemies($dungeonRoute->mappingVersion, 1)->first();
            $this->getService()->broadcastStateIfChanged($liveSession, $liveSession->user);
            $this->storeEnemyState($liveSession, LiveSessionEnemyStateType::Killed, $enemy);

            // Act
            $broadcastResult = $this->getService()->broadcastStateIfChanged($liveSession, $liveSession->user);

            // Assert
            $this->assertSame(LiveSessionStateBroadcastResult::Sent, $broadcastResult);
            Event::assertDispatchedTimes(LiveSessionStateChangedEvent::class, 2);
            Event::assertDispatched(
                LiveSessionStateChangedEvent::class,
                static fn(LiveSessionStateChangedEvent $event): bool => $event->broadcastWith()['state']['killed'] === [$enemy->id],
            );
        } finally {
            $this->cleanupLiveSession($liveSession);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function broadcastStateIfChanged_givenTheRememberedStateWasLost_broadcastsItOnceMore(): void
    {
        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        $liveSession = LiveSession::factory()->create();

        try {
            $this->getService()->broadcastStateIfChanged($liveSession, $liveSession->user);
            Cache::flush();

            // Act
            $broadcastResult = $this->getService()->broadcastStateIfChanged($liveSession, $liveSession->user);
            $this->getService()->broadcastStateIfChanged($liveSession, $liveSession->user);

            // Assert
            $this->assertSame(LiveSessionStateBroadcastResult::Sent, $broadcastResult);
            Event::assertDispatchedTimes(LiveSessionStateChangedEvent::class, 2);
        } finally {
            $dungeonRoute = $liveSession->dungeonRoute;
            $this->cleanupLiveSession($liveSession);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function broadcastStateIfChanged_givenTheBroadcastFailed_broadcastsTheSameStateOnTheNextPass(): void
    {
        // Arrange
        $broadcaster = $this->failBroadcasts(1);

        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $firstBroadcastResult  = $this->getService()->broadcastStateIfChanged($liveSession, $liveSession->user);
            $secondBroadcastResult = $this->getService()->broadcastStateIfChanged($liveSession, $liveSession->user);

            // Assert
            $this->assertSame(LiveSessionStateBroadcastResult::Failed, $firstBroadcastResult);
            $this->assertSame(LiveSessionStateBroadcastResult::Sent, $secondBroadcastResult);
            $this->assertSame(2, $broadcaster->publishCount);
        } finally {
            $dungeonRoute = $liveSession->dungeonRoute;
            $this->cleanupLiveSession($liveSession);
            $dungeonRoute->delete();
        }
    }

    // -------------------------------------------------------------------------
    // Broadcast lock
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('broadcastMethodProvider')]
    public function broadcast_givenTheBroadcastLockHeld_returnsFailedAndDeliversTheStateOnceTheLockIsFree(string $broadcastMethod): void
    {
        // Arrange
        config(['keystoneguru.live_sessions.broadcast_lock_wait_seconds' => 0]);
        $broadcaster = $this->failBroadcasts(0);

        $liveSession = LiveSession::factory()->create();
        $heldLock    = $this->getBroadcastLock($liveSession);

        try {
            $this->assertTrue($heldLock->get(), 'The test must hold the broadcast lock');

            // Act
            $heldBroadcastResult = $this->getService()->{$broadcastMethod}($liveSession, $liveSession->user);
            $heldLock->release();
            $freeBroadcastResult = $this->getService()->broadcastStateIfChanged($liveSession, $liveSession->user);

            // Assert
            $this->assertSame(LiveSessionStateBroadcastResult::Failed, $heldBroadcastResult);
            $this->assertSame(LiveSessionStateBroadcastResult::Sent, $freeBroadcastResult, 'A held lock must leave no state remembered as delivered');
            $this->assertSame(1, $broadcaster->publishCount, 'Only the broadcast with the lock free publishes');
        } finally {
            $heldLock->forceRelease();
            $dungeonRoute = $liveSession->dungeonRoute;
            $this->cleanupLiveSession($liveSession);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    #[DataProvider('broadcastMethodProvider')]
    public function broadcast_givenAKillStoredWhileTakingTheBroadcastLock_publishesTheKill(string $broadcastMethod): void
    {
        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();
        $liveSession  = null;

        try {
            $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);
            $enemy       = $this->uniquelyMatchedEnemies($dungeonRoute->mappingVersion, 1)->firstOrFail();
            $this->whileTheServiceHoldsTheBroadcastLock(
                fn() => $this->storeEnemyState($liveSession, LiveSessionEnemyStateType::Killed, $enemy),
            );

            // Act
            $broadcastResult = $this->getService()->{$broadcastMethod}($liveSession, $liveSession->user);

            // Assert
            $this->assertSame(LiveSessionStateBroadcastResult::Sent, $broadcastResult);
            Event::assertDispatched(
                LiveSessionStateChangedEvent::class,
                static fn(LiveSessionStateChangedEvent $event): bool => $event->broadcastWith()['state']['killed'] === [$enemy->id],
            );
        } finally {
            $this->cleanupLiveSession($liveSession);
            $dungeonRoute->delete();
        }
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function broadcastMethodProvider(): array
    {
        return [
            'broadcastState'          => ['broadcastState'],
            'broadcastStateIfChanged' => ['broadcastStateIfChanged'],
        ];
    }

    #[Test]
    public function broadcastStateIfChanged_givenAFailedASentAndAnUnchangedBroadcast_releasesTheBroadcastLockAfterEach(): void
    {
        // Arrange
        config(['keystoneguru.live_sessions.broadcast_lock_wait_seconds' => 0]);
        $this->failBroadcasts(1);

        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $broadcastResults = [];
            $isLockFreeAfter  = [];
            for ($i = 0; $i < 3; $i++) {
                $broadcastResults[] = $this->getService()->broadcastStateIfChanged($liveSession, $liveSession->user);
                $isLockFreeAfter[]  = $this->isBroadcastLockFree($liveSession);
            }

            // Assert
            $this->assertSame([
                LiveSessionStateBroadcastResult::Failed,
                LiveSessionStateBroadcastResult::Sent,
                LiveSessionStateBroadcastResult::Unchanged,
            ], $broadcastResults);
            $this->assertSame([true, true, true], $isLockFreeAfter);
        } finally {
            $dungeonRoute = $liveSession->dungeonRoute;
            $this->cleanupLiveSession($liveSession);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    #[DataProvider('broadcastMethodProvider')]
    public function broadcast_givenABroadcastLockLease_holdsTheBroadcastLockForThatLease(string $broadcastMethod): void
    {
        // Arrange
        config(['keystoneguru.live_sessions.broadcast_lock_lease_seconds' => 45]);
        Event::fake([LiveSessionStateChangedEvent::class]);
        $this->freezeTime();

        /** @var ArrayStore $store */
        $store = Cache::getStore();
        $this->assertInstanceOf(ArrayStore::class, $store, 'The test reads the lock expiry from the array cache store');

        $liveSession   = LiveSession::factory()->create();
        $lockKey       = sprintf('live-session-state-broadcast-%d', $liveSession->id);
        $lockExpiresAt = null;
        $this->whileTheServiceHoldsTheBroadcastLock(static function () use ($store, $lockKey, &$lockExpiresAt): void {
            $lockExpiresAt = $store->locks[$lockKey]['expiresAt'];
        });

        try {
            // Act
            $broadcastResult = $this->getService()->{$broadcastMethod}($liveSession, $liveSession->user);

            // Assert
            $this->assertSame(LiveSessionStateBroadcastResult::Sent, $broadcastResult);
            $this->assertInstanceOf(CarbonInterface::class, $lockExpiresAt);
            $this->assertEquals(now()->addSeconds(45), $lockExpiresAt);
        } finally {
            $dungeonRoute = $liveSession->dungeonRoute;
            $this->cleanupLiveSession($liveSession);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function broadcastState_givenAnotherSessionsBroadcastHoldingItsLock_broadcastsThisSession(): void
    {
        // Arrange
        config(['keystoneguru.live_sessions.broadcast_lock_wait_seconds' => 0]);
        Event::fake([LiveSessionStateChangedEvent::class]);

        $liveSession          = LiveSession::factory()->create();
        $otherLiveSession     = LiveSession::factory()->create();
        $broadcastResult      = null;
        $otherBroadcastResult = null;
        $this->whileTheServiceHoldsTheBroadcastLock(function () use ($liveSession, $otherLiveSession, &$broadcastResult, &$otherBroadcastResult): void {
            $broadcastResult      = $this->getService()->broadcastState($liveSession, $liveSession->user);
            $otherBroadcastResult = $this->getService()->broadcastState($otherLiveSession, $otherLiveSession->user);
        });

        try {
            // Act
            $this->getService()->broadcastState($otherLiveSession, $otherLiveSession->user);

            // Assert
            $this->assertSame(LiveSessionStateBroadcastResult::Sent, $broadcastResult);
            $this->assertSame(LiveSessionStateBroadcastResult::Failed, $otherBroadcastResult, 'The session whose lock is held waits for it');
            Event::assertDispatchedTimes(LiveSessionStateChangedEvent::class, 2);
        } finally {
            foreach ([$liveSession, $otherLiveSession] as $session) {
                $dungeonRoute = $session->dungeonRoute;
                $this->cleanupLiveSession($session);
                $dungeonRoute->delete();
            }
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function getService(): LiveSessionStateServiceInterface
    {
        return app(LiveSessionStateServiceInterface::class);
    }

    /**
     * Enemies whose npc_id/mdt_id pair matches no other enemy of the mapping version, so a row stored for one of
     * them resolves back to exactly that enemy.
     *
     * @return Collection<int, Enemy>
     */
    private function uniquelyMatchedEnemies(MappingVersion $mappingVersion, int $count): Collection
    {
        return Enemy::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->whereNotNull('floor_id')
            ->orderBy('id')
            ->get()
            ->groupBy(static fn(Enemy $enemy): string => sprintf('%d-%d', $enemy->npc_id, $enemy->mdt_id))
            ->filter(static fn(Collection $enemies): bool => $enemies->count() === 1)
            ->flatten(1)
            ->take($count)
            ->values();
    }

    private function getBroadcastLock(LiveSession $liveSession): Lock
    {
        return Cache::lock(sprintf('live-session-state-broadcast-%d', $liveSession->id), 10);
    }

    private function isBroadcastLockFree(LiveSession $liveSession): bool
    {
        $lock = $this->getBroadcastLock($liveSession);
        if (!$lock->get()) {
            return false;
        }

        $lock->release();

        return true;
    }

    /**
     * Runs $whileHeld once, inside the first broadcast lock the service takes and before the service goes on under it.
     */
    private function whileTheServiceHoldsTheBroadcastLock(callable $whileHeld): void
    {
        $realCacheService = app(CacheServiceInterface::class);
        $cacheService     = Mockery::mock($realCacheService);
        $hasRun           = false;
        $cacheService->shouldReceive('lock')
            ->andReturnUsing(static function (string $key, callable $callable, int $waitFor, int $leaseSeconds = 10) use ($realCacheService, $whileHeld, &$hasRun): mixed {
                return $realCacheService->lock($key, static function () use ($callable, $key, $whileHeld, &$hasRun): mixed {
                    if (!$hasRun && str_starts_with($key, 'live-session-state-broadcast')) {
                        $hasRun = true;
                        $whileHeld();
                    }

                    return $callable();
                }, $waitFor, $leaseSeconds);
            });
        $this->app->instance(CacheServiceInterface::class, $cacheService);
    }

    private function storeEnemyState(LiveSession $liveSession, LiveSessionEnemyStateType $state, Enemy $enemy): void
    {
        LiveSessionEnemyState::query()->create([
            'live_session_id' => $liveSession->id,
            'state'           => $state,
            'npc_id'          => $enemy->npc_id,
            'mdt_id'          => $enemy->mdt_id,
        ]);
    }

    private function storeOverpulledEnemy(LiveSession $liveSession, Enemy $enemy, int $killZoneId): void
    {
        LiveSessionOverpulledEnemy::query()->create([
            'live_session_id' => $liveSession->id,
            'kill_zone_id'    => $killZoneId,
            'npc_id'          => $enemy->npc_id,
            'mdt_id'          => $enemy->mdt_id,
        ]);
    }

    /**
     * @param array<int, int> $obsoleteEnemyIds
     */
    private function mockRouteCorrection(LiveSession $liveSession, array $obsoleteEnemyIds): void
    {
        $routeCorrection = new DungeonRouteCorrection($liveSession);
        $routeCorrection->addObsoleteEnemies(collect($obsoleteEnemyIds));

        $overpulledEnemyService = $this->createMock(OverpulledEnemyServiceInterface::class);
        $overpulledEnemyService->method('getRouteCorrection')->willReturn($routeCorrection);
        $this->app->instance(OverpulledEnemyServiceInterface::class, $overpulledEnemyService);
    }

    /**
     * The size of the HTTP request that publishes the event to Reverb, which Reverb measures against its
     * max_request_size: the request the Pusher SDK builds, plus the headers Guzzle adds when sending it.
     */
    private function getPublishRequestSize(LiveSessionStateChangedEvent $event): int
    {
        $pusher  = new Pusher(str_repeat('k', 20), str_repeat('s', 20), '123456', ['host' => 'reverb', 'port' => 6001, 'scheme' => 'http']);
        $request = $pusher->make_request(
            collect($event->broadcastOn())->map(static fn($channel): string => (string)$channel)->all(),
            $event->broadcastAs(),
            $event->broadcastWith(),
        );
        $body = (string)$request->getBody();

        return strlen(Message::toString($request))
            + strlen("Host: reverb:6001\r\n")
            + strlen("User-Agent: GuzzleHttp/7\r\n")
            + strlen(sprintf("Content-Length: %d\r\n", strlen($body)));
    }

    private function cleanupLiveSession(?LiveSession $liveSession): void
    {
        if ($liveSession === null) {
            return;
        }

        LiveSessionEnemyState::query()->where('live_session_id', $liveSession->id)->delete();
        LiveSessionOverpulledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
        OverpulledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
        LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
        $liveSession->delete();
    }
}
