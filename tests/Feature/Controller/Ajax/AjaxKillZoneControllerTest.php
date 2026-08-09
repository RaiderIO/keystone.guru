<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\PublishedState;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Feature\Controller\DungeonRouteTestBase;
use Tests\Feature\Traits\ProvidesDungeon;

#[Group('Controller')]
#[Group('KillZone')]
final class AjaxKillZoneControllerTest extends DungeonRouteTestBase
{
    use ProvidesDungeon;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);

        // Replace with a non-facade dungeon route so KillZonePathService doesn't fail
        // on facade floor-switch markers when calculateForRoute is called.
        $this->dungeonRoute->delete();
        $this->dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();
        // MySQL can recycle IDs after a restart, leaving orphaned kill_zones rows from a previous
        // test run attached to this newly created route ID. Delete them to start from a clean state.
        $this->dungeonRoute->killZones()->delete();
    }

    #[Test]
    public function storeAll_givenExistingKillZoneId_shouldUpdateKillZone(): void
    {
        // Arrange
        $killZone = KillZone::factory()->create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'floor_id'         => null,
            'lat'              => null,
            'lng'              => null,
            'color'            => '#000000',
            'index'            => 1,
        ]);

        try {
            // Act
            $response = $this->put(sprintf('/ajax/%s/killzone/mass', $this->dungeonRoute->public_key), [
                'killzones' => [
                    [
                        'id'    => $killZone->id,
                        'color' => '#ff0000',
                        'index' => 1,
                    ],
                ],
            ]);

            // Assert
            $response->assertOk();
            $this->assertDatabaseHas('kill_zones', ['id' => $killZone->id, 'color' => '#ff0000']);
        } finally {
            $killZone->delete();
        }
    }

    #[Test]
    public function storeAll_givenNewKillZone_shouldCreateKillZone(): void
    {
        // Act
        $response = $this->put(sprintf('/ajax/%s/killzone/mass', $this->dungeonRoute->public_key), [
            'killzones' => [
                [
                    'color' => '#00ff00',
                    'index' => 1,
                ],
            ],
        ]);

        // Assert
        $response->assertOk();
        $this->assertEquals(1, $this->dungeonRoute->killZones()->count());
    }

    #[Test]
    public function store_givenEnemyIds_shouldSetEnemyIdOnKillZoneEnemies(): void
    {
        // Arrange
        /** @var Enemy $enemy */
        $enemy = Enemy::where('mapping_version_id', $this->dungeonRoute->mapping_version_id)
            ->inRandomOrder()
            ->first();

        try {
            // Act
            $response = $this->post(sprintf('/ajax/%s/killzone', $this->dungeonRoute->public_key), [
                'color'   => '#ff0000',
                'index'   => 1,
                'enemies' => [$enemy->id],
                'spells'  => [],
            ]);

            // Assert
            $response->assertSuccessful();

            /** @var KillZone $killZone */
            $killZone = $this->dungeonRoute->killZones()->first();
            /** @var KillZoneEnemy|null $killZoneEnemy */
            $killZoneEnemy = KillZoneEnemy::where('kill_zone_id', $killZone->id)->first();

            $this->assertNotNull($killZoneEnemy);
            $this->assertEquals($enemy->id, $killZoneEnemy->enemy_id);
        } finally {
            $this->dungeonRoute->killZones()->delete();
        }
    }

    #[Test]
    public function store_givenNewKillZoneOnAnotherUsersRoute_returnsForbidden(): void
    {
        // Arrange
        $nonOwner = User::factory()->create();
        $route    = $this->createRouteOwnedByAnotherUser();

        try {
            $this->actingAs($nonOwner);

            // Act
            $response = $this->post(sprintf('/ajax/%s/killzone', $route->public_key), [
                'color'   => '#ff0000',
                'index'   => 1,
                'enemies' => [],
                'spells'  => [],
            ]);

            // Assert - a new kill zone previously bypassed the edit gate entirely
            $response->assertStatus(StatusCode::FORBIDDEN);
            $this->assertSame(0, $route->killZones()->count());
        } finally {
            $route->killZones()->delete();
            $route->delete();
            $nonOwner->delete();
        }
    }

    /**
     * Guards #3916: store()'s catch(Exception) fallback assigned a plain Response to $result while
     * the method's return type was declared as the non-nullable KillZone, so the fallback itself
     * threw a TypeError instead of ever reaching the client as the intended 404 (this specific
     * trigger - getKillZonePaths() throwing - is #3917's facade-floor crash in practice).
     *
     * Also guards the cold-review follow-up on #3927: the kill zone is already saved and broadcast
     * by the time getKillZonePaths() runs, so a failure there must not be reported as a total
     * failure - the client would never learn the save succeeded, and would retry with an id-less
     * payload, creating a duplicate kill zone.
     */
    #[Test]
    public function store_givenGetKillZonePathsThrows_savesTheKillZoneAndReturnsEmptyPathsInsteadOfFailingTheWholeRequest(): void
    {
        // Arrange
        $killZonePathServiceMock = Mockery::mock(KillZonePathServiceInterface::class);
        /** @var Mockery\Expectation $expectation */
        $expectation = $killZonePathServiceMock->shouldReceive('calculateForRoute');
        $expectation->andThrow(new \Exception('boom'));
        app()->instance(KillZonePathServiceInterface::class, $killZonePathServiceMock);

        try {
            // Act
            $response = $this->post(sprintf('/ajax/%s/killzone', $this->dungeonRoute->public_key), [
                'color'   => '#ff0000',
                'index'   => 1,
                'enemies' => [],
                'spells'  => [],
            ]);

            // Assert - no TypeError, no 404: the save itself succeeded, only the cosmetic paths
            // add-on degrades
            $response->assertSuccessful();
            $response->assertJsonPath('killzone_paths', []);
            $this->assertSame(1, $this->dungeonRoute->killZones()->count());
        } finally {
            $this->dungeonRoute->killZones()->delete();
        }
    }

    #[Test]
    public function storeAll_givenAnotherUsersRoute_returnsForbidden(): void
    {
        // Arrange
        $nonOwner = User::factory()->create();
        $route    = $this->createRouteOwnedByAnotherUser();

        try {
            $this->actingAs($nonOwner);

            // Act
            $response = $this->put(sprintf('/ajax/%s/killzone/mass', $route->public_key), [
                'killzones' => [
                    [
                        'color' => '#00ff00',
                        'index' => 1,
                    ],
                ],
            ]);

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
            $this->assertSame(0, $route->killZones()->count());
        } finally {
            $route->killZones()->delete();
            $route->delete();
            $nonOwner->delete();
        }
    }

    #[Test]
    public function storeAll_givenKillZoneOfAnotherUsersRoute_returnsForbidden(): void
    {
        // Arrange - the actor owns $this->dungeonRoute but targets a kill zone that belongs to
        // someone else's route. authorizeKillZoneEdit() re-authorizes each batch item against the
        // kill zone's own route before saveKillZone() is ever called.
        $nonOwner      = User::factory()->create();
        $otherRoute    = $this->createRouteOwnedByAnotherUser();
        $otherKillZone = KillZone::factory()->create([
            'dungeon_route_id' => $otherRoute->id,
            'floor_id'         => null,
            'lat'              => null,
            'lng'              => null,
            'color'            => '#000000',
            'index'            => 1,
        ]);

        try {
            $this->actingAs($nonOwner);

            // Act - $this->dungeonRoute is a sandbox route, so the outer edit gate lets us in
            $response = $this->put(sprintf('/ajax/%s/killzone/mass', $this->dungeonRoute->public_key), [
                'killzones' => [
                    [
                        'id'    => $otherKillZone->id,
                        'color' => '#ff0000',
                        'index' => 1,
                    ],
                ],
            ]);

            // Assert - a 403, not the generic 404 the surrounding catch would otherwise produce
            $response->assertStatus(StatusCode::FORBIDDEN);
            $this->assertSame('#000000', $otherKillZone->fresh()->color);
        } finally {
            $otherKillZone->delete();
            $otherRoute->delete();
            $nonOwner->delete();
        }
    }

    #[Test]
    public function storeAll_givenEnemyIds_shouldSetEnemyIdOnKillZoneEnemies(): void
    {
        // Arrange
        /** @var Enemy $enemy */
        $enemy    = Enemy::where('mapping_version_id', $this->dungeonRoute->mapping_version_id)->inRandomOrder()->first();
        $killZone = KillZone::factory()->create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'floor_id'         => null,
            'lat'              => null,
            'lng'              => null,
            'color'            => '#000000',
            'index'            => 1,
        ]);

        try {
            // Act
            $response = $this->put(sprintf('/ajax/%s/killzone/mass', $this->dungeonRoute->public_key), [
                'killzones' => [
                    [
                        'id'      => $killZone->id,
                        'color'   => '#ff0000',
                        'index'   => 1,
                        'enemies' => [$enemy->id],
                    ],
                ],
            ]);

            // Assert
            $response->assertOk();

            $killZoneEnemy = KillZoneEnemy::where('kill_zone_id', $killZone->id)->first();
            $this->assertNotNull($killZoneEnemy);
            $this->assertEquals($enemy->id, $killZoneEnemy->enemy_id);
        } finally {
            $killZone->delete();
        }
    }

    /**
     * Guards #3873: deleteAll() used to mass-delete the killZones relation via the query builder,
     * which never fires {@see KillZone::deleting} and silently orphaned its killZoneEnemies rows.
     */
    #[Test]
    public function deleteAll_givenKillZoneWithEnemies_deletesItThroughItsOwnDeletingHook(): void
    {
        // Arrange
        /** @var Enemy $enemy */
        $enemy    = Enemy::where('mapping_version_id', $this->dungeonRoute->mapping_version_id)->inRandomOrder()->first();
        $killZone = KillZone::factory()->create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'floor_id'         => null,
            'lat'              => null,
            'lng'              => null,
            'color'            => '#000000',
            'index'            => 1,
        ]);
        $killZoneEnemy = KillZoneEnemy::create([
            'kill_zone_id' => $killZone->id,
            'enemy_id'     => $enemy->id,
        ]);

        try {
            // Act
            $response = $this->delete(sprintf('/ajax/%s/killzone', $this->dungeonRoute->public_key), [
                'confirm' => 'yes',
            ]);

            // Assert
            $response->assertOk();
            $this->assertDatabaseMissing('kill_zones', ['id' => $killZone->id]);
            $this->assertDatabaseMissing('kill_zone_enemies', ['id' => $killZoneEnemy->id]);
        } finally {
            KillZoneEnemy::query()->where('id', $killZoneEnemy->id)->delete();
            KillZone::query()->where('id', $killZone->id)->delete();
        }
    }

    /**
     * Guards #3917: the client places a pull's kill area in whatever plane the map it rendered was
     * using, so on a facade (combined multi-floor) map it submits the facade floor and facade-plane
     * coordinates. saveKillZone() used to convert those only when the *acting user's* map facade
     * style said facade - state the save request need not share with the request that rendered the
     * map (it leaks across requests on an Octane worker) - and stored the facade floor verbatim
     * otherwise. The conversion must be driven by the submitted floor instead.
     */
    #[Test]
    public function store_givenAFacadeLocationWhileTheMapFacadeStyleSaysSplitFloors_persistsTheLocationOnItsRealFloor(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: true, minEnemies: 1);
        /** @var Floor $facadeFloor */
        $facadeFloor = $dungeon->floors()->where('facade', true)->firstOrFail();
        /** @var Enemy $enemy */
        $enemy = $mappingVersion->enemies()->whereHas('floor', static fn($query) => $query->where('facade', false))
            ->whereNotNull('lat')
            ->firstOrFail();

        /** @var CoordinatesServiceInterface $coordinatesService */
        $coordinatesService = app(CoordinatesServiceInterface::class);
        // Exactly the location the client would submit for a kill area placed on top of that enemy
        // while the facade map is rendered
        $facadeLatLng = $coordinatesService->convertMapLocationToFacadeMapLocation($mappingVersion, $enemy->getLatLng());
        $this->assertTrue((bool)$facadeLatLng->getFloor()->facade, 'Expected the enemy to live inside a floor union area');

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        // The state an /embed, /explore or /heatmap request handled by the same worker leaves behind
        User::forceMapFacadeStyle(User::MAP_FACADE_STYLE_SPLIT_FLOORS);

        try {
            // Act
            $response = $this->post(sprintf('/ajax/%s/killzone', $dungeonRoute->public_key), [
                'color'    => '#ff0000',
                'index'    => 1,
                'floor_id' => $facadeFloor->id,
                'lat'      => $facadeLatLng->getLat(),
                'lng'      => $facadeLatLng->getLng(),
                'enemies'  => [],
                'spells'   => [],
            ]);

            // Assert
            $response->assertSuccessful();

            /** @var KillZone $killZone */
            $killZone = $dungeonRoute->killZones()->firstOrFail();
            $this->assertNotEquals($facadeFloor->id, $killZone->floor_id);
            $this->assertFalse((bool)$killZone->floor->facade);
            // Converting the enemy's own location and back must land on the enemy's floor again
            $this->assertEquals($enemy->floor_id, $killZone->floor_id);

            // ...but the response still echoes the location back in the plane the client renders,
            // so the marker the user just placed does not jump
            $response->assertJsonPath('floor_id', $facadeFloor->id);
        } finally {
            User::forceMapFacadeStyle(null);
            $dungeonRoute->killZones()->delete();
            $dungeonRoute->delete();
        }
    }

    /**
     * Guards #3917: a kill zone that already carries a facade floor_id on a route whose mapping
     * version does not render a facade cannot be converted - convertFacadeMapLocationToMapLocation()
     * hands the facade floor straight back. Rejecting the save would make every such already-corrupted
     * pull uneditable, which is worse than the bug; the location is dropped instead so the save lands.
     */
    #[Test]
    public function store_givenAFacadeFloorOnARouteThatDoesNotRenderAFacade_dropsTheLocationInsteadOfFailingTheSave(): void
    {
        // Arrange - a facade floor that has nothing to do with this (non-facade) route's dungeon,
        // exactly the shape of a row corrupted before this fix
        /** @var Floor $foreignFacadeFloor */
        $foreignFacadeFloor = Floor::where('facade', true)->firstOrFail();
        $this->assertFalse((bool)$this->dungeonRoute->mappingVersion->facade_enabled);

        $killZone = KillZone::factory()->create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'floor_id'         => $foreignFacadeFloor->id,
            'lat'              => -100.0,
            'lng'              => 100.0,
            'color'            => '#000000',
            'index'            => 1,
        ]);

        try {
            // Act - the client echoes the stored location back while changing something else
            $response = $this->put(sprintf('/ajax/%s/killzone/%s', $this->dungeonRoute->public_key, $killZone->id), [
                'color'    => '#ff0000',
                'index'    => 1,
                'floor_id' => $foreignFacadeFloor->id,
                'lat'      => -100.0,
                'lng'      => 100.0,
                'enemies'  => [],
                'spells'   => [],
            ]);

            // Assert - the save succeeds, and the unusable location is gone rather than re-persisted
            $response->assertSuccessful();
            $this->assertDatabaseHas('kill_zones', [
                'id'       => $killZone->id,
                'color'    => '#ff0000',
                'floor_id' => null,
                'lat'      => null,
                'lng'      => null,
            ]);
        } finally {
            KillZone::query()->where('id', $killZone->id)->delete();
        }
    }

    /**
     * A published, non-sandbox route authored by user 1. Sandbox routes (expires_at set, which the
     * factory does by default) are editable by anyone by design, so expires_at must be null for an
     * authorization assertion to mean anything.
     */
    private function createRouteOwnedByAnotherUser(): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'author_id'          => 1,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'expires_at'         => null,
        ]);
    }
}
