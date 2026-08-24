<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemy;
use App\Models\LiveSession;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\DungeonRouteTestBase;

#[Group('Controller')]
#[Group('OverpulledEnemy')]
final class AjaxOverpulledEnemyControllerTest extends DungeonRouteTestBase
{
    private LiveSession $liveSession;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);

        $this->liveSession = LiveSession::create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'user_id'          => $this->dungeonRoute->author_id,
            'public_key'       => LiveSession::generateRandomPublicKey(),
        ]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            OverpulledEnemy::query()->where('live_session_id', $this->liveSession->id)->delete();
            // Mass delete on purpose: LiveSession's "deleting" hook cascades into overpulled_enemies,
            // which this test has already cleaned up above
            LiveSession::query()->whereKey($this->liveSession->id)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function store_givenSeveralEnemies_marksThemAllOverpulled(): void
    {
        // Arrange
        $enemies = $this->distinctEnemies(3);

        // Act
        $response = $this->post($this->url(), [
            'kill_zone_id' => 1,
            'enemy_ids'    => $enemies->pluck('id')->toArray(),
        ]);

        // Assert
        $response->assertOk();
        $this->assertEquals(
            $enemies->count(),
            OverpulledEnemy::query()->where('live_session_id', $this->liveSession->id)->count(),
        );
    }

    /**
     * Guards #4264: store() saved one overpulled enemy per loop iteration with no transaction around
     * the batch, so a failure partway through committed the enemies saved before it while the client
     * was told the whole request failed. Overpulling a pack is one user action - it lands whole or
     * not at all.
     */
    #[Test]
    public function store_givenOneEnemyOfTheBatchFails_savesNoneOfThem(): void
    {
        // Arrange
        $enemies = $this->distinctEnemies(3);

        // Fail the third write, by which point the first two have already been inserted inside the
        // same transaction - exactly the state that used to get committed
        $saveCount = 0;
        OverpulledEnemy::creating(static function () use (&$saveCount): bool {
            if (++$saveCount === 3) {
                throw new Exception('Simulated failure saving the overpulled enemy');
            }

            return true;
        });

        try {
            // Act
            $response = $this->post($this->url(), [
                'kill_zone_id' => 1,
                'enemy_ids'    => $enemies->pluck('id')->toArray(),
            ]);

            // Assert - the client is told it failed, and the live session shows no half-applied pull
            $response->assertStatus(500);
            $this->assertEquals(0, OverpulledEnemy::query()->where('live_session_id', $this->liveSession->id)->count());
        } finally {
            // Remove only the listener registered above - OverpulledEnemy::flushEventListeners()
            // would also wipe its own boot() listeners for the rest of the PHPUnit process
            Event::forget('eloquent.creating: ' . OverpulledEnemy::class);
        }
    }

    /**
     * @return Collection<int, Enemy>
     */
    private function distinctEnemies(int $count): Collection
    {
        // The controller keys overpulled enemies on (npc_id, mdt_id), so two enemies sharing that
        // pair would collapse into a single row and make the counts below meaningless
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
