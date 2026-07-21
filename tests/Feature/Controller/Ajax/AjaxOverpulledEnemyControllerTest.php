<?php

namespace Tests\Feature\Controller\Ajax;

use App\Events\LiveSession\RouteCorrectionEvent;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
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
        // Arrange - regression guard: the route correction used to be recomputed and broadcast once
        // per enemy inside the delete loop instead of once after it
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
}
