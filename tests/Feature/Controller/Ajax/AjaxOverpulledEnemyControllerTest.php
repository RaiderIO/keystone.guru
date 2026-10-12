<?php

namespace Tests\Feature\Controller\Ajax;

use App\Events\LiveSession\RouteCorrectionEvent;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\DungeonRouteTestBase;

#[Group('Controller')]
#[Group('AjaxOverpulledEnemyController')]
final class AjaxOverpulledEnemyControllerTest extends DungeonRouteTestBase
{
    private LiveSession $liveSession;

    private KillZone $killZone;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);

        $this->liveSession = LiveSession::factory()->create([
            'dungeon_route_id' => $this->dungeonRoute->id,
        ]);

        $this->killZone = KillZone::factory()->create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'index'            => 1,
        ]);
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
    public function store_givenValidRequest_dispatchesRouteCorrectionEvent(): void
    {
        // Arrange
        Event::fake([RouteCorrectionEvent::class]);

        /** @var Enemy|null $enemy */
        $enemy = $this->dungeonRoute->mappingVersion->enemies()->first();

        if ($enemy === null) {
            $this->markTestSkipped('No enemies found for dungeon route mapping version');
        }

        // Act
        $this->postJson(
            sprintf('/ajax/%s/live/%s/overpulledenemy', $this->dungeonRoute->public_key, $this->liveSession->public_key),
            [
                'enemy_ids'    => [$enemy->id],
                'kill_zone_id' => $this->killZone->id,
            ],
        )->assertOk();

        // Assert
        Event::assertDispatched(RouteCorrectionEvent::class);
    }

    // -------------------------------------------------------------------------
    // delete
    // -------------------------------------------------------------------------

    #[Test]
    public function delete_givenValidRequest_dispatchesRouteCorrectionEvent(): void
    {
        // Arrange
        Event::fake([RouteCorrectionEvent::class]);

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
            Event::assertDispatched(RouteCorrectionEvent::class);
        } finally {
            LiveSessionOverpulledEnemy::query()
                ->where('live_session_id', $this->liveSession->id)
                ->where('npc_id', $enemy->npc_id)
                ->where('mdt_id', $enemy->mdt_id)
                ->delete();
        }
    }

    #[Test]
    public function delete_givenMultipleEnemies_dispatchesRouteCorrectionEventOnlyOnce(): void
    {
        // Arrange - the route correction must be computed and broadcast once after the delete loop,
        // not once per enemy inside it
        Event::fake([RouteCorrectionEvent::class]);

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
            Event::assertDispatchedTimes(RouteCorrectionEvent::class, 1);
        } finally {
            LiveSessionOverpulledEnemy::query()
                ->where('live_session_id', $this->liveSession->id)
                ->whereIn('npc_id', $enemies->pluck('npc_id'))
                ->delete();
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
            'kill_zone_id' => 1,
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
            'kill_zone_id' => 1,
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
        $enemies = $this->distinctEnemies(2);
        $this->markOverpulled($enemies);

        // Act
        $response = $this->delete($this->url(), [
            'kill_zone_id' => 1,
            'enemy_ids'    => $enemies->pluck('id')->toArray(),
            'no_result'    => true,
        ]);

        // Assert
        $response->assertNoContent();
        $this->assertEquals(0, LiveSessionOverpulledEnemy::query()->where('live_session_id', $this->liveSession->id)->count());
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
            'kill_zone_id' => 1,
            'enemy_ids'    => $enemies->pluck('id')->toArray(),
        ]);

        // Assert
        $response->assertForbidden();
        $this->assertEquals(2, LiveSessionOverpulledEnemy::query()->where('live_session_id', $this->liveSession->id)->count());
    }

    /**
     * @param Collection<int, Enemy> $enemies
     */
    private function markOverpulled(Collection $enemies): void
    {
        foreach ($enemies as $enemy) {
            LiveSessionOverpulledEnemy::create([
                'live_session_id' => $this->liveSession->id,
                'kill_zone_id'    => 1,
                'npc_id'          => $enemy->npc_id,
                'mdt_id'          => $enemy->mdt_id,
            ]);
        }
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

    private function url(): string
    {
        return sprintf(
            '/ajax/%s/live/%s/overpulledenemy',
            $this->dungeonRoute->getRouteKey(),
            $this->liveSession->getRouteKey(),
        );
    }
}
