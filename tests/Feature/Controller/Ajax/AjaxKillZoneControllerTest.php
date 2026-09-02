<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteChange;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
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
    public function storeAll_givenNonexistentKillZoneId_createsTheKillZoneWithADatabaseAssignedId(): void
    {
        // Arrange - an id no kill zone has, so the batch entry falls through to the create branch
        $clientSuppliedId = 2_000_000_000;
        $this->assertNull(KillZone::find($clientSuppliedId));

        // Act
        $response = $this->put(sprintf('/ajax/%s/killzone/mass', $this->dungeonRoute->public_key), [
            'killzones' => [
                [
                    'id'    => $clientSuppliedId,
                    'color' => '#00ff00',
                    'index' => 1,
                ],
            ],
        ]);

        // Assert
        $response->assertOk();
        $killZones = $this->dungeonRoute->killZones()->get();
        $this->assertCount(1, $killZones);
        $this->assertNotSame($clientSuppliedId, $killZones->first()->id);
        $this->assertNull(KillZone::find($clientSuppliedId));
    }

    #[Test]
    public function storeAll_givenNonexistentKillZoneIdWithEnemies_attachesTheEnemiesToTheCreatedKillZone(): void
    {
        // Arrange
        $clientSuppliedId = 2_000_000_000;
        $this->assertNull(KillZone::find($clientSuppliedId));
        // The test database persists between runs - orphans a previous run may have left behind
        // under this id would otherwise mask the assertion below
        KillZoneEnemy::query()->where('kill_zone_id', $clientSuppliedId)->delete();
        $enemies = $this->getRouteEnemies(2);

        try {
            // Act
            $response = $this->put(sprintf('/ajax/%s/killzone/mass', $this->dungeonRoute->public_key), [
                'killzones' => [
                    [
                        'id'      => $clientSuppliedId,
                        'color'   => '#00ff00',
                        'index'   => 1,
                        'enemies' => $enemies->pluck('id')->toArray(),
                    ],
                ],
            ]);

            // Assert
            $response->assertOk();
            $killZones = $this->dungeonRoute->killZones()->get();
            $this->assertCount(1, $killZones);
            $killZone = $killZones->first();
            $this->assertNotSame($clientSuppliedId, $killZone->id);
            $this->assertSame([$killZone->id], $response->json('killzone_ids'));

            $this->assertEqualsCanonicalizing(
                $enemies->pluck('id')->toArray(),
                KillZoneEnemy::where('kill_zone_id', $killZone->id)->pluck('enemy_id')->toArray(),
            );
            $this->assertSame(0, KillZoneEnemy::where('kill_zone_id', $clientSuppliedId)->count());
        } finally {
            $this->deleteKillZones();
        }
    }

    #[Test]
    public function storeAll_givenTwoEntriesSharingANonexistentKillZoneId_createsTwoKillZonesEachWithTheirOwnEnemies(): void
    {
        // Arrange
        $clientSuppliedId = 2_000_000_000;
        $this->assertNull(KillZone::find($clientSuppliedId));
        // The test database persists between runs - orphans a previous run may have left behind
        // under this id would otherwise mask the assertion below
        KillZoneEnemy::query()->where('kill_zone_id', $clientSuppliedId)->delete();
        $enemies       = $this->getRouteEnemies(2);
        $firstEnemyId  = $enemies->first()->id;
        $secondEnemyId = $enemies->last()->id;

        try {
            // Act
            $response = $this->put(sprintf('/ajax/%s/killzone/mass', $this->dungeonRoute->public_key), [
                'killzones' => [
                    [
                        'id'      => $clientSuppliedId,
                        'color'   => '#00ff00',
                        'index'   => 1,
                        'enemies' => [$firstEnemyId],
                    ],
                    [
                        'id'      => $clientSuppliedId,
                        'color'   => '#0000ff',
                        'index'   => 2,
                        'enemies' => [$secondEnemyId],
                    ],
                ],
            ]);

            // Assert
            $response->assertOk();
            $killZones = $this->dungeonRoute->killZones()->orderBy('index')->get();
            $this->assertCount(2, $killZones);
            $this->assertNotContains($clientSuppliedId, $killZones->pluck('id')->toArray());
            $this->assertSame($killZones->pluck('id')->toArray(), $response->json('killzone_ids'));

            $this->assertSame([$firstEnemyId], KillZoneEnemy::where('kill_zone_id', $killZones->get(0)->id)->pluck('enemy_id')->toArray());
            $this->assertSame([$secondEnemyId], KillZoneEnemy::where('kill_zone_id', $killZones->get(1)->id)->pluck('enemy_id')->toArray());
            $this->assertSame(0, KillZoneEnemy::where('kill_zone_id', $clientSuppliedId)->count());
        } finally {
            $this->deleteKillZones();
        }
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
        /** @var CoordinatesServiceInterface $coordinatesService */
        $coordinatesService = app(CoordinatesServiceInterface::class);

        // Floor union areas cover only part of a floor, so an arbitrary enemy need not sit inside
        // one - and taking the first enemy and asserting that it did made this test fail for
        // whichever facade dungeon the shuffle happened to pick (lowerkarazhan, 1 of 53). The
        // conversion is therefore a *requirement* on the fixture: reject any dungeon with no enemy
        // that converts onto the facade floor, and hand back the enemy that does.
        [$dungeon, $mappingVersion, $enemy] = $this->findDungeon(
            facadeEnabled: true,
            minEnemies:    1,
            resolve:       static function (Dungeon $dungeon, MappingVersion $mappingVersion) use ($coordinatesService): ?Enemy {
                // `floor` is eager loaded because the coordinate conversion reads it, and these
                // enemies are hydrated as a collection - where preventLazyLoading does enforce
                $enemies = $mappingVersion->enemies()
                    ->with('floor')
                    ->whereHas('floor', static fn($query) => $query->where('facade', false))
                    ->whereNotNull('lat')
                    ->get();

                foreach ($enemies as $enemy) {
                    /** @var Enemy $enemy */
                    $facadeLatLng = $coordinatesService->convertMapLocationToFacadeMapLocation($mappingVersion, $enemy->getLatLng());

                    if ((bool)$facadeLatLng->getFloor()->facade) {
                        return $enemy;
                    }
                }

                return null;
            },
        );

        /** @var Floor $facadeFloor */
        $facadeFloor = $dungeon->floors()->where('facade', true)->firstOrFail();

        // Exactly the location the client would submit for a kill area placed on top of that enemy
        // while the facade map is rendered
        $facadeLatLng = $coordinatesService->convertMapLocationToFacadeMapLocation($mappingVersion, $enemy->getLatLng());

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        // A persisted preference rather than User::forceMapFacadeStyle(), which the new
        // ResetsMapFacadeStyleOverride middleware would clear before the controller ever read it -
        // this is the surviving mismatch: the user flipped the setting in another tab while this map
        // tab kept rendering the facade, so the client still submits facade coordinates
        /** @var User $user */
        $user                   = User::findOrFail(1);
        $originalMapFacadeStyle = $user->map_facade_style;
        $user->update(['map_facade_style' => User::MAP_FACADE_STYLE_SPLIT_FLOORS]);
        $this->actingAs($user->fresh());

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
            $user->update(['map_facade_style' => $originalMapFacadeStyle]);
            $dungeonRoute->killZones()->delete();
            $dungeonRoute->delete();
        }
    }

    /**
     * Guards #3917: a location the user just placed on a facade map that belongs to no real floor -
     * the dead space of the combined image - must be refused with feedback rather than persisted as
     * a facade floor or silently dropped. This is the only branch that can hard-fail a save, so it
     * must stay reachable only for a genuinely new location (see the sibling test below).
     */
    #[Test]
    public function store_givenANewFacadeLocationThatBelongsToNoRealFloor_refusesTheSave(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: true);
        /** @var Floor $facadeFloor */
        $facadeFloor = $dungeon->floors()->where('facade', true)->firstOrFail();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        try {
            // Act - far outside the map, so no floor union area can contain it
            $response = $this->post(sprintf('/ajax/%s/killzone', $dungeonRoute->public_key), [
                'color'    => '#ff0000',
                'index'    => 1,
                'floor_id' => $facadeFloor->id,
                'lat'      => -100000.0,
                'lng'      => 100000.0,
                'enemies'  => [],
                'spells'   => [],
            ]);

            // Assert - refused, and nothing was written
            $response->assertStatus(422);
            $this->assertSame(0, $dungeonRoute->killZones()->count());
        } finally {
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
     * Guards #4260: saveKillZone() wrote a pull's enemies as `killZoneEnemies()->delete()` followed
     * by `KillZoneEnemy::insert()` with nothing wrapping the pair, so a failure in the gap committed
     * the delete and never ran the insert - the pull was permanently emptied while the client was
     * told the save had failed.
     */
    #[Test]
    public function store_givenAFailureAfterTheEnemiesWereRewritten_leavesThePullsPreviousContentsIntact(): void
    {
        // Arrange - a pull that already holds two enemies, and a third one to swap them out for
        $enemies = $this->getRouteEnemies(3);

        $killZone = $this->createKillZoneWithEnemies($enemies->take(2));

        // The last write of the unit, so everything the save did is already in place by the time it
        // throws - the exact window in which the pull used to end up with no enemies at all
        DungeonRouteChange::creating(static function (): never {
            throw new Exception('Simulated failure recording the route change');
        });

        try {
            // Act
            $response = $this->put(sprintf('/ajax/%s/killzone/%s', $this->dungeonRoute->public_key, $killZone->id), [
                'color'   => '#ff0000',
                'index'   => 1,
                'enemies' => [$enemies->get(2)->id],
                'spells'  => [],
            ]);

            // Assert - the client is told it failed, and the pull still holds exactly what it did
            $response->assertStatus(StatusCode::NOT_FOUND);
            $this->assertEqualsCanonicalizing(
                $enemies->take(2)->pluck('id')->all(),
                KillZoneEnemy::query()->where('kill_zone_id', $killZone->id)->pluck('enemy_id')->all(),
            );
            $this->assertSame('#000000', $killZone->fresh()->color);
        } finally {
            // Remove only the listener registered above - flushEventListeners() would also wipe the
            // model's own boot() listeners for the rest of the PHPUnit process
            Event::forget('eloquent.creating: ' . DungeonRouteChange::class);
            $this->deleteKillZones();
        }
    }

    /**
     * Guards #4260: storeAll()'s writes spanned the whole batch, so a failure on any one pull left
     * every pull the loop had already saved committed while the client was told the batch failed -
     * and the client resubmits the batch it thinks was never applied.
     */
    #[Test]
    public function storeAll_givenAFailureOnTheSecondPull_rollsBackTheFirstOneToo(): void
    {
        // Arrange
        $enemies = $this->getRouteEnemies(2);

        $firstKillZone  = $this->createKillZoneWithEnemies($enemies->take(1), 1);
        $secondKillZone = $this->createKillZoneWithEnemies($enemies->skip(1), 2);

        // Fail on the second pull only, once the first one is fully written
        $changes = 0;
        DungeonRouteChange::creating(static function () use (&$changes): void {
            $changes++;

            if ($changes > 1) {
                throw new Exception('Simulated failure recording the route change');
            }
        });

        try {
            // Act
            $response = $this->put(sprintf('/ajax/%s/killzone/mass', $this->dungeonRoute->public_key), [
                'killzones' => [
                    ['id' => $firstKillZone->id, 'color' => '#ff0000', 'index' => 1],
                    ['id' => $secondKillZone->id, 'color' => '#ff0000', 'index' => 2],
                ],
            ]);

            // Assert - the whole batch is rolled back, not just the pull that failed
            $response->assertStatus(StatusCode::NOT_FOUND);
            $this->assertSame('#000000', $firstKillZone->fresh()->color);
            $this->assertSame('#000000', $secondKillZone->fresh()->color);
        } finally {
            Event::forget('eloquent.creating: ' . DungeonRouteChange::class);
            $this->deleteKillZones();
        }
    }

    /**
     * Guards #4260: delete() is six writes - KillZone::deleting cascading into kill_zone_enemies and
     * kill_zone_spells, the row itself, the route's enemy_forces, a change log row and a touch() -
     * none of which were wrapped, so a failure part-way through left the pull and its enemies gone
     * while responding 404.
     */
    #[Test]
    public function delete_givenAFailureAfterThePullWasDeleted_leavesThePullAndItsEnemiesIntact(): void
    {
        // Arrange
        $killZone = $this->createKillZoneWithEnemies($this->getRouteEnemies(1));

        DungeonRouteChange::creating(static function (): never {
            throw new Exception('Simulated failure recording the route change');
        });

        try {
            // Act
            $response = $this->delete(sprintf('/ajax/%s/killzone/%s', $this->dungeonRoute->public_key, $killZone->id));

            // Assert
            $response->assertStatus(StatusCode::NOT_FOUND);
            $this->assertDatabaseHas('kill_zones', ['id' => $killZone->id]);
            $this->assertSame(1, KillZoneEnemy::query()->where('kill_zone_id', $killZone->id)->count());
        } finally {
            Event::forget('eloquent.creating: ' . DungeonRouteChange::class);
            $this->deleteKillZones();
        }
    }

    /**
     * Guards #4260: a deadlock is retried, and Model::delete() flips `exists` to false on the PHP
     * object - which a rollback does not undo. Reusing the same instance on the second attempt
     * therefore made delete() return null without issuing any SQL, so the pull survived while the
     * request reported it as an unrecoverable failure.
     */
    #[Test]
    public function delete_givenADeadlockOnTheFirstAttempt_deletesThePullOnTheRetry(): void
    {
        // Arrange
        $killZone = $this->createKillZoneWithEnemies($this->getRouteEnemies(1));

        // The message is what Laravel's concurrency detector matches on, so this drives the real
        // DB::transaction() retry path rather than a simulation of it
        $attempts = 0;
        DungeonRouteChange::creating(static function () use (&$attempts): void {
            $attempts++;

            if ($attempts === 1) {
                throw new Exception('Deadlock found when trying to get lock; try restarting transaction');
            }
        });

        try {
            // Act
            $response = $this->delete(sprintf('/ajax/%s/killzone/%s', $this->dungeonRoute->public_key, $killZone->id));

            // Assert - the retry actually deleted, rather than reporting success (or a 500) over a no-op
            $response->assertOk();
            $this->assertSame(2, $attempts);
            $this->assertDatabaseMissing('kill_zones', ['id' => $killZone->id]);
            $this->assertSame(0, KillZoneEnemy::query()->where('kill_zone_id', $killZone->id)->count());
        } finally {
            Event::forget('eloquent.creating: ' . DungeonRouteChange::class);
            $this->deleteKillZones();
        }
    }

    /**
     * Guards #4260: deleteAll() looped a six-write delete over every pull with nothing wrapping it,
     * so a failure part-way through left the route half-cleared - some pulls gone, some still there,
     * and the client told the whole thing failed.
     */
    #[Test]
    public function deleteAll_givenAFailurePartWayThrough_leavesEveryPullIntact(): void
    {
        // Arrange
        $enemies = $this->getRouteEnemies(2);

        $firstKillZone  = $this->createKillZoneWithEnemies($enemies->take(1), 1);
        $secondKillZone = $this->createKillZoneWithEnemies($enemies->skip(1), 2);

        // Fail once the first pull is already deleted, which is what used to get committed
        $changes = 0;
        DungeonRouteChange::creating(static function () use (&$changes): void {
            $changes++;

            if ($changes > 1) {
                throw new Exception('Simulated failure recording the route change');
            }
        });

        try {
            // Act
            $response = $this->delete(sprintf('/ajax/%s/killzone', $this->dungeonRoute->public_key), [
                'confirm' => 'yes',
            ]);

            // Assert
            $response->assertStatus(StatusCode::NOT_FOUND);
            $this->assertDatabaseHas('kill_zones', ['id' => $firstKillZone->id]);
            $this->assertDatabaseHas('kill_zones', ['id' => $secondKillZone->id]);
            $this->assertSame(2, KillZoneEnemy::query()->whereIn('kill_zone_id', [$firstKillZone->id, $secondKillZone->id])->count());
        } finally {
            Event::forget('eloquent.creating: ' . DungeonRouteChange::class);
            $this->deleteKillZones();
        }
    }

    /**
     * @return Collection<int, Enemy>
     */
    private function getRouteEnemies(int $count): Collection
    {
        /** @var Collection<int, Enemy> $enemies */
        $enemies = Enemy::query()
            ->where('mapping_version_id', $this->dungeonRoute->mapping_version_id)
            ->limit($count)
            ->get();

        $this->assertCount($count, $enemies, 'The fixture route does not have enough enemies for this test');

        return $enemies;
    }

    /**
     * @param Collection<int, Enemy> $enemies
     */
    private function createKillZoneWithEnemies(Collection $enemies, int $index = 1): KillZone
    {
        /** @var KillZone $killZone */
        $killZone = KillZone::factory()->create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'floor_id'         => null,
            'lat'              => null,
            'lng'              => null,
            'color'            => '#000000',
            'index'            => $index,
        ]);

        foreach ($enemies as $enemy) {
            KillZoneEnemy::create([
                'kill_zone_id' => $killZone->id,
                'npc_id'       => $enemy->mdt_npc_id ?? $enemy->npc_id,
                'mdt_id'       => $enemy->mdt_id,
                'enemy_id'     => $enemy->id,
            ]);
        }

        return $killZone;
    }

    private function deleteKillZones(): void
    {
        $killZoneIds = KillZone::query()->where('dungeon_route_id', $this->dungeonRoute->id)->pluck('id');

        KillZoneEnemy::query()->whereIn('kill_zone_id', $killZoneIds)->delete();
        KillZone::query()->whereIn('id', $killZoneIds)->delete();

        // DungeonRoute::deleting does not cascade these, so tearDown's route delete would leave the
        // rows these tests' saves and deletes recorded behind
        DungeonRouteChange::query()->where('dungeon_route_id', $this->dungeonRoute->id)->delete();
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
