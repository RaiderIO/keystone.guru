<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemies\PridefulEnemy;
use App\Models\Enemy;
use App\Models\PublishedState;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Feature\Controller\DungeonRouteTestBase;

#[Group('Controller')]
#[Group('PridefulEnemy')]
final class AjaxPridefulEnemyControllerTest extends DungeonRouteTestBase
{
    #[\Override]
    protected function tearDown(): void
    {
        try {
            PridefulEnemy::query()->where('dungeon_route_id', $this->dungeonRoute->id)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function store_givenAValidEnemy_marksItPridefulAndTouchesTheRoute(): void
    {
        // Arrange
        $enemy = $this->randomEnemy();

        // Age the route so that touch() actually moves updated_at - the route was created moments
        // ago in setUp(), and Eloquent writes nothing when the new second-precision timestamp
        // equals the stored one
        DungeonRoute::query()->whereKey($this->dungeonRoute->id)->update(['updated_at' => now()->subHour()]);
        $updatedBefore = $this->dungeonRoute->fresh()->updated_at;

        // Act
        $response = $this->post($this->url($enemy), [
            'floor_id' => $enemy->floor_id,
            'lat'      => $enemy->lat,
            'lng'      => $enemy->lng,
        ]);

        // Assert
        $response->assertCreated();
        $this->assertEquals(1, PridefulEnemy::query()->where('dungeon_route_id', $this->dungeonRoute->id)->count());
        $this->assertDatabaseHas(PridefulEnemy::class, [
            'dungeon_route_id' => $this->dungeonRoute->id,
            'enemy_id'         => $enemy->id,
            'floor_id'         => $enemy->floor_id,
        ]);
        $this->assertNotEquals($updatedBefore, $this->dungeonRoute->fresh()->updated_at);
    }

    /**
     * Guards #4264: store() saved the prideful enemy and then touched the route to refresh its
     * thumbnail, with no transaction around the pair. A failure at the touch committed the prideful
     * enemy anyway, so the route kept advertising a thumbnail that no longer matched its contents.
     */
    #[Test]
    public function store_givenTheRouteTouchFails_rollsBackThePridefulEnemy(): void
    {
        // Arrange - fail the touch, which is the write that follows the prideful enemy save
        $enemy = $this->randomEnemy();

        // Age the route so that touch() actually makes updated_at dirty - the route was created
        // moments ago in setUp(), and Eloquent skips the write (and the 'updating' event) entirely
        // when the new second-precision timestamp equals the stored one
        DungeonRoute::query()->whereKey($this->dungeonRoute->id)->update(['updated_at' => now()->subHour()]);

        DungeonRoute::updating(static function (): never {
            throw new Exception('Simulated failure touching the dungeon route');
        });

        try {
            // Act
            $response = $this->post($this->url($enemy), [
                'floor_id' => $enemy->floor_id,
                'lat'      => $enemy->lat,
                'lng'      => $enemy->lng,
            ]);

            // Assert - nothing was left behind that the thumbnail no longer reflects
            $response->assertStatus(StatusCode::INTERNAL_SERVER_ERROR);
            $this->assertEquals(0, PridefulEnemy::query()->where('dungeon_route_id', $this->dungeonRoute->id)->count());
        } finally {
            // Remove only the listener registered above - DungeonRoute::flushEventListeners() would
            // also wipe DungeonRoute::boot()'s own listeners for the rest of the PHPUnit process
            Event::forget('eloquent.updating: ' . DungeonRoute::class);
        }
    }

    #[Test]
    public function store_givenAnEnemyThatIsAlreadyPrideful_updatesTheExistingRow(): void
    {
        // Arrange
        $enemy = $this->randomEnemy();
        $this->post($this->url($enemy), [
            'floor_id' => $enemy->floor_id,
            'lat'      => $enemy->lat,
            'lng'      => $enemy->lng,
        ])->assertCreated();

        // Act
        $response = $this->post($this->url($enemy), [
            'floor_id' => $enemy->floor_id,
            'lat'      => -12.5,
            'lng'      => 34.5,
        ]);

        // Assert
        $response->assertOk();
        /** @var PridefulEnemy $pridefulEnemy */
        $pridefulEnemy = PridefulEnemy::query()->where('dungeon_route_id', $this->dungeonRoute->id)->sole();
        $this->assertEquals(-12.5, $pridefulEnemy->lat);
        $this->assertEquals(34.5, $pridefulEnemy->lng);
    }

    #[Test]
    public function delete_givenAPridefulEnemy_removesIt(): void
    {
        // Arrange
        $enemy = $this->randomEnemy();
        $this->post($this->url($enemy), [
            'floor_id' => $enemy->floor_id,
            'lat'      => $enemy->lat,
            'lng'      => $enemy->lng,
        ])->assertCreated();

        // Act
        $response = $this->delete($this->url($enemy));

        // Assert
        $response->assertNoContent();
        $this->assertEquals(0, PridefulEnemy::query()->where('dungeon_route_id', $this->dungeonRoute->id)->count());
    }

    #[Test]
    public function store_givenAUserWhoCannotEditTheDungeonRoute_returnsForbidden(): void
    {
        // Arrange
        $enemy = $this->randomEnemy();
        $this->dungeonRoute->update([
            'author_id'          => 1,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
        $outsider = User::factory()->create();

        try {
            $this->actingAs($outsider);

            // Act
            $response = $this->post($this->url($enemy), [
                'floor_id' => $enemy->floor_id,
                'lat'      => $enemy->lat,
                'lng'      => $enemy->lng,
            ]);

            // Assert
            $response->assertForbidden();
            $this->assertEquals(0, PridefulEnemy::query()->where('dungeon_route_id', $this->dungeonRoute->id)->count());
        } finally {
            $outsider->delete();
        }
    }

    private function randomEnemy(): Enemy
    {
        /** @var Enemy $enemy */
        $enemy = $this->dungeonRoute->mappingVersion->enemies()->get()->random();

        return $enemy;
    }

    private function url(Enemy $enemy): string
    {
        return sprintf('/ajax/%s/pridefulenemy/%s', $this->dungeonRoute->getRouteKey(), $enemy->getRouteKey());
    }
}
