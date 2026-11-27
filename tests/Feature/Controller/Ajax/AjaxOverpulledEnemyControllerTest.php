<?php

namespace Tests\Feature\Controller\Ajax;

use App\Events\LiveSession\LiveSessionStateChangedEvent;
use App\Jobs\LiveSession\ProcessLiveSessionCombatLogBuffer;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionEnemyState;
use App\Models\LiveSession\LiveSessionEnemyStateType;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Service\LiveSession\LiveSessionOverpullDetectionServiceInterface;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode\Http;
use Tests\Feature\Controller\DungeonRouteTestBase;
use Tests\Fixtures\Traits\FailsBroadcasts;

#[Group('Controller')]
#[Group('AjaxOverpulledEnemyController')]
final class AjaxOverpulledEnemyControllerTest extends DungeonRouteTestBase
{
    use FailsBroadcasts;

    private LiveSession $liveSession;

    private KillZone $killZone;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);

        $this->liveSession = $this->createLiveSession($this->dungeonRoute);
        $this->killZone    = $this->createKillZone($this->dungeonRoute);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->killZone->delete();

        LiveSessionOverpulledEnemy::query()->where('live_session_id', $this->liveSession->id)->delete();
        OverpulledEnemy::query()->where('live_session_id', $this->liveSession->id)->delete();
        $this->liveSession->delete();

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    #[Test]
    public function store_givenValidRequest_broadcastsTheStateWithTheEnemyOverpulled(): void
    {
        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        /** @var Enemy|null $enemy */
        $enemy = $this->dungeonRoute->mappingVersion->enemies()->first();

        if ($enemy === null) {
            $this->markTestSkipped('No enemies found for dungeon route mapping version');
        }

        // Act
        $response = $this->postJson(
            sprintf('/ajax/%s/live/%s/overpulledenemy', $this->dungeonRoute->public_key, $this->liveSession->public_key),
            [
                'enemy_ids'    => [$enemy->id],
                'kill_zone_id' => $this->killZone->id,
            ],
        );

        // Assert
        $response->assertOk();
        $response->assertJsonStructure(['obsolete_enemy_ids', 'enemy_forces']);
        Event::assertDispatchedTimes(LiveSessionStateChangedEvent::class, 1);
        Event::assertDispatched(
            LiveSessionStateChangedEvent::class,
            fn(LiveSessionStateChangedEvent $event): bool => in_array([$enemy->id, $this->killZone->id], $event->broadcastWith()['state']['overpulled'], true),
        );
    }

    // -------------------------------------------------------------------------
    // delete
    // -------------------------------------------------------------------------

    #[Test]
    public function delete_givenValidRequest_broadcastsTheStateWithoutTheOverpull(): void
    {
        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        /** @var Enemy|null $enemy */
        $enemy = $this->dungeonRoute->mappingVersion->enemies()->first();

        if ($enemy === null) {
            $this->markTestSkipped('No enemies found for dungeon route mapping version');
        }

        LiveSessionOverpulledEnemy::query()->create([
            'live_session_id' => $this->liveSession->id,
            'kill_zone_id'    => $this->killZone->id,
            'npc_id'          => $enemy->npc_id,
            'mdt_id'          => $enemy->mdt_id,
        ]);

        try {
            // Act
            $this->deleteJson(
                sprintf('/ajax/%s/live/%s/overpulledenemy', $this->dungeonRoute->public_key, $this->liveSession->public_key),
                [
                    'enemy_ids'    => [$enemy->id],
                    'kill_zone_id' => $this->killZone->id,
                    'no_result'    => false,
                ],
            )->assertOk();

            // Assert
            Event::assertDispatched(
                LiveSessionStateChangedEvent::class,
                static fn(LiveSessionStateChangedEvent $event): bool => $event->broadcastWith()['state']['overpulled'] === [],
            );
        } finally {
            LiveSessionOverpulledEnemy::query()
                ->where('live_session_id', $this->liveSession->id)
                ->where('npc_id', $enemy->npc_id)
                ->where('mdt_id', $enemy->mdt_id)
                ->delete();
        }
    }

    #[Test]
    public function delete_givenMultipleEnemies_broadcastsTheStateOnlyOnce(): void
    {
        // Arrange - the state must be broadcast once after the delete loop, not once per enemy inside it
        Event::fake([LiveSessionStateChangedEvent::class]);

        /** @var \Illuminate\Support\Collection<int, Enemy> $enemies */
        $enemies = $this->dungeonRoute->mappingVersion->enemies()->limit(2)->get();

        if ($enemies->count() < 2) {
            $this->markTestSkipped('Fewer than 2 enemies found for dungeon route mapping version');
        }

        foreach ($enemies as $enemy) {
            LiveSessionOverpulledEnemy::query()->create([
                'live_session_id' => $this->liveSession->id,
                'kill_zone_id'    => $this->killZone->id,
                'npc_id'          => $enemy->npc_id,
                'mdt_id'          => $enemy->mdt_id,
            ]);
        }

        try {
            // Act
            $this->deleteJson(
                sprintf('/ajax/%s/live/%s/overpulledenemy', $this->dungeonRoute->public_key, $this->liveSession->public_key),
                [
                    'enemy_ids'    => $enemies->pluck('id')->all(),
                    'kill_zone_id' => $this->killZone->id,
                    'no_result'    => false,
                ],
            )->assertOk();

            // Assert
            Event::assertDispatchedTimes(LiveSessionStateChangedEvent::class, 1);
        } finally {
            LiveSessionOverpulledEnemy::query()
                ->where('live_session_id', $this->liveSession->id)
                ->whereIn('npc_id', $enemies->pluck('npc_id'))
                ->delete();
        }
    }

    #[Test]
    public function store_givenLiveSessionOfAnotherRoute_returnsNotFoundAndSavesNothing(): void
    {
        // Arrange
        $enemies      = $this->distinctEnemies(1);
        $otherRoute   = $this->createNonFacadeDungeonRouteWithEnemies();
        $otherSession = $this->createLiveSession($otherRoute);

        try {
            // Act
            $response = $this->post($this->url($otherSession), [
                'kill_zone_id' => $this->killZone->id,
                'enemy_ids'    => $enemies->pluck('id')->toArray(),
            ]);

            // Assert
            $response->assertNotFound();
            $this->assertEquals(0, LiveSessionOverpulledEnemy::query()->where('live_session_id', $otherSession->id)->count());
        } finally {
            LiveSessionOverpulledEnemy::query()->where('live_session_id', $otherSession->id)->delete();
            OverpulledEnemy::query()->where('live_session_id', $otherSession->id)->delete();
            LiveSession::query()->whereKey($otherSession->id)->delete();
            $otherRoute->delete();
        }
    }

    #[Test]
    public function delete_givenLiveSessionOfAnotherRoute_returnsNotFoundAndDeletesNothing(): void
    {
        // Arrange
        $enemies       = $this->distinctEnemies(1);
        $otherRoute    = $this->createNonFacadeDungeonRouteWithEnemies();
        $otherSession  = $this->createLiveSession($otherRoute);
        $otherKillZone = $this->createKillZone($otherRoute);
        LiveSessionOverpulledEnemy::create([
            'live_session_id' => $otherSession->id,
            'kill_zone_id'    => $otherKillZone->id,
            'npc_id'          => $enemies->first()->npc_id,
            'mdt_id'          => $enemies->first()->mdt_id,
        ]);

        try {
            // Act
            $response = $this->delete($this->url($otherSession), [
                'kill_zone_id' => $this->killZone->id,
                'enemy_ids'    => $enemies->pluck('id')->toArray(),
            ]);

            // Assert
            $response->assertNotFound();
            $this->assertEquals(1, LiveSessionOverpulledEnemy::query()->where('live_session_id', $otherSession->id)->count());
        } finally {
            LiveSessionOverpulledEnemy::query()->where('live_session_id', $otherSession->id)->delete();
            OverpulledEnemy::query()->where('live_session_id', $otherSession->id)->delete();
            LiveSession::query()->whereKey($otherSession->id)->delete();
            KillZone::query()->whereKey($otherKillZone->id)->delete();
            $otherRoute->delete();
        }
    }

    #[Test]
    public function store_givenKillZoneOfAnotherRoute_returnsValidationErrorAndSavesNothing(): void
    {
        // Arrange
        $enemies       = $this->distinctEnemies(1);
        $otherRoute    = $this->createNonFacadeDungeonRouteWithEnemies();
        $otherKillZone = $this->createKillZone($otherRoute);

        try {
            // Act
            $response = $this->postJson($this->url(), [
                'kill_zone_id' => $otherKillZone->id,
                'enemy_ids'    => $enemies->pluck('id')->toArray(),
            ]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['kill_zone_id']);
            $this->assertEquals(0, LiveSessionOverpulledEnemy::query()->where('live_session_id', $this->liveSession->id)->count());
        } finally {
            KillZone::query()->whereKey($otherKillZone->id)->delete();
            $otherRoute->delete();
        }
    }

    #[Test]
    public function store_givenSeveralEnemies_marksThemAllOverpulled(): void
    {
        // Arrange
        $enemies = $this->distinctEnemies(3);

        // Act
        $response = $this->post($this->url(), [
            'kill_zone_id' => $this->killZone->id,
            'enemy_ids'    => $enemies->pluck('id')->toArray(),
        ]);

        // Assert
        $response->assertOk();
        $this->assertEquals(
            $enemies->count(),
            LiveSessionOverpulledEnemy::query()->where('live_session_id', $this->liveSession->id)->count(),
        );
    }

    /**
     * Overpulling a pack is one user action - a failure partway through the batch must not commit
     * the enemies saved before it while the client is told the whole request failed.
     */
    #[Test]
    public function store_givenOneEnemyOfTheBatchFails_savesNoneOfThem(): void
    {
        // Arrange
        $enemies = $this->distinctEnemies(3);

        // Fail the third write, by which point the first two have already been inserted inside the
        // same transaction
        $saveCount = 0;
        LiveSessionOverpulledEnemy::creating(static function () use (&$saveCount): bool {
            if (++$saveCount === 3) {
                throw new Exception('Simulated failure saving the overpulled enemy');
            }

            return true;
        });

        try {
            // Act
            $response = $this->post($this->url(), [
                'kill_zone_id' => $this->killZone->id,
                'enemy_ids'    => $enemies->pluck('id')->toArray(),
            ]);

            // Assert - the client is told it failed, and the live session shows no half-applied pull
            $response->assertStatus(500);
            $this->assertEquals(0, LiveSessionOverpulledEnemy::query()->where('live_session_id', $this->liveSession->id)->count());
        } finally {
            // Remove only the listener registered above - LiveSessionOverpulledEnemy::flushEventListeners()
            // would also wipe its own boot() listeners for the rest of the PHPUnit process
            Event::forget('eloquent.creating: ' . LiveSessionOverpulledEnemy::class);
        }
    }

    #[Test]
    public function store_givenAnExpiredLiveSession_returnsForbiddenAndSavesNothing(): void
    {
        // Arrange
        $enemies = $this->distinctEnemies(1);
        LiveSession::query()->whereKey($this->liveSession->id)->update(['expires_at' => now()->subHour()]);

        // Act
        $response = $this->post($this->url(), [
            'kill_zone_id' => $this->killZone->id,
            'enemy_ids'    => $enemies->pluck('id')->toArray(),
        ]);

        // Assert
        $response->assertForbidden();
        $this->assertEquals(0, LiveSessionOverpulledEnemy::query()->where('live_session_id', $this->liveSession->id)->count());
    }

    #[Test]
    public function delete_givenOverpulledEnemies_unmarksThemAndReturnsTheRouteCorrection(): void
    {
        // Arrange
        $enemies = $this->distinctEnemies(3);
        $this->markOverpulled($enemies);

        // Act
        $response = $this->delete($this->url(), [
            'kill_zone_id' => $this->killZone->id,
            'enemy_ids'    => $enemies->take(2)->pluck('id')->toArray(),
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonStructure(['obsolete_enemy_ids', 'enemy_forces']);
        $this->assertEquals(
            [$enemies->last()->mdt_id],
            LiveSessionOverpulledEnemy::query()->where('live_session_id', $this->liveSession->id)->pluck('mdt_id')->toArray(),
        );
    }

    #[Test]
    public function delete_givenNoResult_unmarksTheEnemiesAndReturnsNoContent(): void
    {
        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);
        $enemies = $this->distinctEnemies(2);
        $this->markOverpulled($enemies);

        // Act
        $response = $this->delete($this->url(), [
            'kill_zone_id' => $this->killZone->id,
            'enemy_ids'    => $enemies->pluck('id')->toArray(),
            'no_result'    => true,
        ]);

        // Assert
        $response->assertNoContent();
        $this->assertEquals(0, LiveSessionOverpulledEnemy::query()->where('live_session_id', $this->liveSession->id)->count());
        Event::assertDispatchedTimes(LiveSessionStateChangedEvent::class, 1);
    }

    #[Test]
    public function delete_givenAnOverpullDetectedFromTheLog_isNotRecreatedWhenTheKillIsReplayed(): void
    {
        // Arrange
        $enemy                    = $this->distinctEnemies(1)->firstOrFail();
        $overpullDetectionService = app(LiveSessionOverpullDetectionServiceInterface::class);
        $overpullDetectionService->processResolvedKills($this->liveSession, collect([$enemy]), collect());
        $this->assertSame(1, $this->overpulledEnemyQuery($enemy)->count(), 'The pass must detect the off-route kill as an overpull');

        // Act
        $this->delete($this->url(), [
            'kill_zone_id' => $this->killZone->id,
            'enemy_ids'    => [$enemy->id],
            'no_result'    => true,
        ])->assertNoContent();
        $overpullDetectionService->processResolvedKills($this->liveSession, collect([$enemy]), collect());

        // Assert
        $this->assertSame(0, $this->overpulledEnemyQuery($enemy)->count());
    }

    #[Test]
    public function store_givenAnEnemyWhoseOverpullWasDismissed_clearsTheDismissalAndALaterPassKeepsTheMark(): void
    {
        // Arrange
        $enemy                    = $this->distinctEnemies(1)->firstOrFail();
        $overpullDetectionService = app(LiveSessionOverpullDetectionServiceInterface::class);
        $overpullDetectionService->processResolvedKills($this->liveSession, collect([$enemy]), collect());
        $this->delete($this->url(), [
            'kill_zone_id' => $this->killZone->id,
            'enemy_ids'    => [$enemy->id],
            'no_result'    => true,
        ])->assertNoContent();
        $this->assertSame(1, $this->overpullDismissedQuery($enemy)->count(), 'Taking the overpull back must record the dismissal');

        // Act
        $this->post($this->url(), [
            'kill_zone_id' => $this->killZone->id,
            'enemy_ids'    => [$enemy->id],
        ])->assertOk();
        $overpullDetectionService->processResolvedKills($this->liveSession, collect([$enemy]), collect());

        // Assert
        $this->assertSame(0, $this->overpullDismissedQuery($enemy)->count());
        $this->assertSame([$this->killZone->id], $this->overpulledEnemyQuery($enemy)->pluck('kill_zone_id')->all());
    }

    #[Test]
    public function store_givenTheOverpullLockHeldByOverpullDetection_returnsConflictAndSavesNothing(): void
    {
        // Arrange
        config(['keystoneguru.live_sessions.overpull_lock_wait_seconds' => 0]);
        $enemies = $this->distinctEnemies(1);

        // Act
        $response = $this->whileHoldingTheOverpullLock(fn(): TestResponse => $this->post($this->url(), [
            'kill_zone_id' => $this->killZone->id,
            'enemy_ids'    => $enemies->pluck('id')->toArray(),
        ]));

        // Assert
        $response->assertStatus(Http::CONFLICT);
        $this->assertEquals(0, LiveSessionOverpulledEnemy::query()->where('live_session_id', $this->liveSession->id)->count());
    }

    #[Test]
    public function delete_givenTheOverpullLockHeldByOverpullDetection_returnsConflictAndDeletesNothing(): void
    {
        // Arrange
        config(['keystoneguru.live_sessions.overpull_lock_wait_seconds' => 0]);
        $enemy = $this->distinctEnemies(1)->firstOrFail();
        $this->markOverpulled(collect([$enemy]));

        // Act
        $response = $this->whileHoldingTheOverpullLock(fn(): TestResponse => $this->delete($this->url(), [
            'kill_zone_id' => $this->killZone->id,
            'enemy_ids'    => [$enemy->id],
            'no_result'    => true,
        ]));

        // Assert
        $response->assertStatus(Http::CONFLICT);
        $this->assertSame(1, $this->overpulledEnemyQuery($enemy)->count());
        $this->assertSame(0, $this->overpullDismissedQuery($enemy)->count());
    }

    #[Test]
    public function store_givenTheBroadcastFails_respondsAsUsualAndDispatchesTheBufferJob(): void
    {
        // Arrange
        Queue::fake([ProcessLiveSessionCombatLogBuffer::class]);
        $this->failBroadcasts();
        $enemies = $this->distinctEnemies(1);

        // Act
        $response = $this->post($this->url(), [
            'kill_zone_id' => $this->killZone->id,
            'enemy_ids'    => $enemies->pluck('id')->toArray(),
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonStructure(['obsolete_enemy_ids', 'enemy_forces']);
        Queue::assertPushed(
            ProcessLiveSessionCombatLogBuffer::class,
            fn(ProcessLiveSessionCombatLogBuffer $job): bool => $job->liveSessionId === $this->liveSession->id,
        );
    }

    #[Test]
    public function store_givenTheBroadcastSucceeds_dispatchesNoBufferJob(): void
    {
        // Arrange
        Queue::fake([ProcessLiveSessionCombatLogBuffer::class]);
        $enemies = $this->distinctEnemies(1);

        // Act
        $response = $this->post($this->url(), [
            'kill_zone_id' => $this->killZone->id,
            'enemy_ids'    => $enemies->pluck('id')->toArray(),
        ]);

        // Assert
        $response->assertOk();
        Queue::assertNotPushed(ProcessLiveSessionCombatLogBuffer::class);
    }

    #[Test]
    public function delete_givenTheBroadcastFails_respondsAsUsualAndDispatchesTheBufferJob(): void
    {
        // Arrange
        Queue::fake([ProcessLiveSessionCombatLogBuffer::class]);
        $this->failBroadcasts();
        $enemy = $this->distinctEnemies(1)->firstOrFail();
        $this->markOverpulled(collect([$enemy]));

        // Act
        $response = $this->delete($this->url(), [
            'kill_zone_id' => $this->killZone->id,
            'enemy_ids'    => [$enemy->id],
            'no_result'    => true,
        ]);

        // Assert
        $response->assertNoContent();
        $this->assertSame(0, $this->overpulledEnemyQuery($enemy)->count());
        Queue::assertPushed(
            ProcessLiveSessionCombatLogBuffer::class,
            fn(ProcessLiveSessionCombatLogBuffer $job): bool => $job->liveSessionId === $this->liveSession->id,
        );
    }

    #[Test]
    public function delete_givenTheBroadcastSucceeds_dispatchesNoBufferJob(): void
    {
        // Arrange
        Queue::fake([ProcessLiveSessionCombatLogBuffer::class]);
        $enemy = $this->distinctEnemies(1)->firstOrFail();
        $this->markOverpulled(collect([$enemy]));

        // Act
        $response = $this->delete($this->url(), [
            'kill_zone_id' => $this->killZone->id,
            'enemy_ids'    => [$enemy->id],
            'no_result'    => true,
        ]);

        // Assert
        $response->assertNoContent();
        Queue::assertNotPushed(ProcessLiveSessionCombatLogBuffer::class);
    }

    #[Test]
    public function delete_givenTheBroadcastKeepsFailingOnTheSyncQueue_retriesItOnceAndResponds(): void
    {
        // Arrange
        $broadcaster = $this->failBroadcasts();
        $enemy       = $this->distinctEnemies(1)->firstOrFail();
        $this->markOverpulled(collect([$enemy]));

        // Act
        $response = $this->delete($this->url(), [
            'kill_zone_id' => $this->killZone->id,
            'enemy_ids'    => [$enemy->id],
            'no_result'    => true,
        ]);

        // Assert
        $response->assertNoContent();
        $this->assertSame(2, $broadcaster->publishCount, 'The request\'s own broadcast and one by the job it dispatched');
    }

    #[Test]
    public function delete_givenAnExpiredLiveSession_returnsForbiddenAndDeletesNothing(): void
    {
        // Arrange
        $enemies = $this->distinctEnemies(2);
        $this->markOverpulled($enemies);
        LiveSession::query()->whereKey($this->liveSession->id)->update(['expires_at' => now()->subHour()]);

        // Act
        $response = $this->delete($this->url(), [
            'kill_zone_id' => $this->killZone->id,
            'enemy_ids'    => $enemies->pluck('id')->toArray(),
        ]);

        // Assert
        $response->assertForbidden();
        $this->assertEquals(2, LiveSessionOverpulledEnemy::query()->where('live_session_id', $this->liveSession->id)->count());
    }

    /**
     * @param callable(): TestResponse<\Symfony\Component\HttpFoundation\Response> $request
     *
     * @return TestResponse<\Symfony\Component\HttpFoundation\Response>
     */
    private function whileHoldingTheOverpullLock(callable $request): TestResponse
    {
        return app(OverpulledEnemyServiceInterface::class)->withOverpullLock($this->liveSession, $request);
    }

    /**
     * @param Collection<int, Enemy> $enemies
     */
    private function markOverpulled(Collection $enemies): void
    {
        foreach ($enemies as $enemy) {
            LiveSessionOverpulledEnemy::create([
                'live_session_id' => $this->liveSession->id,
                'kill_zone_id'    => $this->killZone->id,
                'enemy_id'        => $enemy->id,
                'npc_id'          => $enemy->npc_id,
                'mdt_id'          => $enemy->mdt_id,
            ]);
        }
    }

    /**
     * @return Builder<LiveSessionOverpulledEnemy>
     */
    private function overpulledEnemyQuery(Enemy $enemy): Builder
    {
        return LiveSessionOverpulledEnemy::query()
            ->where('live_session_id', $this->liveSession->id)
            ->where('npc_id', $enemy->npc_id)
            ->where('mdt_id', $enemy->mdt_id);
    }

    /**
     * @return Builder<LiveSessionEnemyState>
     */
    private function overpullDismissedQuery(Enemy $enemy): Builder
    {
        return LiveSessionEnemyState::query()
            ->where('live_session_id', $this->liveSession->id)
            ->where('state', LiveSessionEnemyStateType::OverpullDismissed)
            ->where('npc_id', $enemy->npc_id)
            ->where('mdt_id', $enemy->mdt_id);
    }

    /**
     * @return Collection<int, Enemy>
     */
    private function distinctEnemies(int $count): Collection
    {
        // The controller keys overpulled enemies on (npc_id, mdt_id), so two enemies sharing that
        // pair would collapse into a single row and make the counts meaningless
        return $this->dungeonRoute->mappingVersion->enemies()
            ->get()
            ->unique(static fn(Enemy $enemy) => sprintf('%d-%d', $enemy->npc_id, $enemy->mdt_id))
            ->take($count)
            ->values();
    }

    private function url(?LiveSession $liveSession = null): string
    {
        return sprintf(
            '/ajax/%s/live/%s/overpulledenemy',
            $this->dungeonRoute->getRouteKey(),
            ($liveSession ?? $this->liveSession)->getRouteKey(),
        );
    }

    private function createLiveSession(DungeonRoute $dungeonRoute): LiveSession
    {
        return LiveSession::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'user_id'          => $dungeonRoute->author_id,
            'public_key'       => LiveSession::generateRandomPublicKey(),
        ]);
    }

    private function createKillZone(DungeonRoute $dungeonRoute): KillZone
    {
        /** @var KillZone $killZone */
        $killZone = KillZone::factory()->create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => null,
            'lat'              => null,
            'lng'              => null,
            'color'            => '#000000',
            'index'            => 1,
        ]);

        return $killZone;
    }
}
