<?php

namespace Tests\Feature\Controller\Ajax;

use App\Http\Requests\Ajax\AjaxAdminCombatLogRouteGetEnemyResolutionLinesFormRequest;
use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use App\Models\User;
use App\Service\CombatLog\Dtos\CombatLogRouteEnemyFailureHeatmapResult;
use App\Service\CombatLog\Dtos\CombatLogRouteEnemyResolutionHeatmapResult;
use App\Service\CombatLog\Enums\EnemyResolutionHeatmapMetric;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('AjaxAdminCombatLogRoute')]
final class AjaxAdminCombatLogRouteControllerTest extends AjaxPublicTestCase
{
    use ProvidesDungeon;

    private Dungeon $dungeon;

    private Floor $floor;

    private MappingVersion $mappingVersion;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Without Accept: application/json the ValidationException handler redirects (302)
        // instead of returning a JSON 422 response.
        $this->defaultHeaders['Accept'] = 'application/json';

        // The default facade style collapses all heatmap data onto the facade floor, which makes
        // assertions on a specific (non-facade) floor_id non-deterministic depending on the dungeon
        // picked below. Force split floors so real floor ids are preserved in the response.
        User::forceMapFacadeStyle(User::MAP_FACADE_STYLE_SPLIT_FLOORS);

        // Some seeded floors have no ingame coordinates set, which throws once a cluster/heatmap
        // response tries to convert lat/lng for that floor - so the resolved floor must actually
        // carry coordinates rather than just be non-facade.
        [$this->dungeon, $this->mappingVersion, $this->floor] = $this->findDungeon(
            facadeEnabled: false,
            resolve:       static fn(Dungeon $dungeon) => $dungeon->floors()->where('facade', 0)->where('ingame_max_x', '!=', 0)->first(),
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        User::forceMapFacadeStyle(null);

        parent::tearDown();
    }

    #[Test]
    public function getEnemyFailures_givenNoDungeonId_returnsValidationError(): void
    {
        // Act
        $response = $this->get(route('ajax.admin.combatlogroute.enemy_failures'));

        // Assert
        $response->assertUnprocessable();
    }

    #[Test]
    public function getEnemyFailures_givenNoMappingVersionId_returnsValidationError(): void
    {
        // Act
        $response = $this->get(route('ajax.admin.combatlogroute.enemy_failures', [
            'dungeon_id' => $this->dungeon->id,
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('mapping_version_id');
    }

    #[Test]
    public function getEnemyFailureClusters_givenNoMappingVersionId_returnsValidationError(): void
    {
        // Act
        $response = $this->get(route('ajax.admin.combatlogroute.enemy_failures.clusters', [
            'dungeon_id' => $this->dungeon->id,
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('mapping_version_id');
    }

    #[Test]
    public function getEnemyFailureClusters_givenValidDungeon_returnsClusterResponseShape(): void
    {
        $created          = [];
        $npcEnemyForcesId = null;

        try {
            // Arrange — a handful of failures for one npc in one spot. Only npcs worth enemy forces are analysed
            // at all (#4475), so the npc gets some.
            $npcEnemyForcesId = NpcEnemyForces::query()->create([
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => 99905,
                'enemy_forces'       => 10,
            ])->id;

            $floor = $this->floor;
            for ($i = 0; $i < 6; $i++) {
                $created[] = CombatLogRouteEnemyFailure::create([
                    'dungeon_route_id'   => 8000 + $i,
                    'dungeon_id'         => $this->dungeon->id,
                    'floor_id'           => $floor->id,
                    'mapping_version_id' => $this->mappingVersion->id,
                    'npc_id'             => 99905,
                    'lat'                => -100.0 + $i * 0.01,
                    'lng'                => 150.0,
                ])->id;
            }

            // Act
            $response = $this->get(route('ajax.admin.combatlogroute.enemy_failures.clusters', [
                'dungeon_id'         => $this->dungeon->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => [99905],
            ]));

            // Assert
            $response->assertOk();
            $response->assertJsonStructure([
                'data' => [['npc_id', 'npc_name', 'floor_id', 'count', 'route_count', 'avg_failures_per_route', 'centroid' => ['lat', 'lng', 'floor_id'], 'hull', 'verdict', 'low_volume', 'suggestion', 'nearest_enemy_id', 'nearest_enemy_distance', 'enemies_within_range']],
                'verdicts',
                'cluster_radius_yd',
                'min_count',
                'min_routes',
                'skipped_count',
            ]);
            $this->assertSame(99905, $response->json('data.0.npc_id'));
            $this->assertSame(6, $response->json('data.0.count'));
            $this->assertSame('npc_not_mapped', $response->json('data.0.verdict'));
        } finally {
            CombatLogRouteEnemyFailure::whereIn('id', $created)->delete();

            if ($npcEnemyForcesId !== null) {
                NpcEnemyForces::query()->whereKey($npcEnemyForcesId)->delete();
            }
        }
    }

    #[Test]
    public function getEnemyFailureClusters_givenNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $nonAdmin = User::factory()->create();

        try {
            $this->actingAs($nonAdmin);

            // Act
            $response = $this->get(route('ajax.admin.combatlogroute.enemy_failures.clusters', [
                'dungeon_id'         => $this->dungeon->id,
                'mapping_version_id' => $this->mappingVersion->id,
            ]));

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $nonAdmin->delete();
        }
    }

    #[Test]
    public function deleteEnemyFailures_givenNoDungeonId_returnsValidationError(): void
    {
        // Act
        $response = $this->delete(route('ajax.admin.combatlogroute.enemy_failures.delete'));

        // Assert
        $response->assertUnprocessable();
    }

    #[Test]
    public function getEnemyFailures_givenValidDungeon_returnsFullHeatmapResponseShape(): void
    {
        $created = [];

        try {
            // Arrange — two records far enough apart to land in distinct grid cells
            $failure1 = CombatLogRouteEnemyFailure::create([
                'dungeon_id'         => $this->dungeon->id,
                'floor_id'           => $this->floor->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => null,
                'lat'                => -50.0,
                'lng'                => 100.0,
            ]);
            $created[] = $failure1->id;

            $failure2 = CombatLogRouteEnemyFailure::create([
                'dungeon_id'         => $this->dungeon->id,
                'floor_id'           => $this->floor->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => null,
                'lat'                => -200.0,
                'lng'                => 300.0,
            ]);
            $created[] = $failure2->id;

            // Act
            $response = $this->get(route('ajax.admin.combatlogroute.enemy_failures', [
                'dungeon_id'         => $this->dungeon->id,
                'mapping_version_id' => $this->mappingVersion->id,
            ]));

            // Assert
            $response->assertOk();

            $body = json_decode($response->content(), true);
            $this->assertArrayHasKey('data', $body);
            $this->assertArrayHasKey('data_type', $body);
            $this->assertArrayHasKey('weight_max', $body);
            $this->assertArrayHasKey('failure_count', $body);
            $this->assertArrayHasKey('grid_size_x', $body);
            $this->assertArrayHasKey('grid_size_y', $body);

            $this->assertEquals(CombatLogRouteEnemyFailureHeatmapResult::DATA_TYPE, $body['data_type']);
            $this->assertGreaterThan(0, $body['grid_size_x']);
            $this->assertGreaterThan(0, $body['grid_size_y']);

            /** @var array<int, array<string, mixed>> $bodyData */
            $bodyData   = $body['data'];
            $floorEntry = collect($bodyData)->firstWhere('floor_id', $this->floor->id);
            $this->assertNotNull($floorEntry);

            foreach ($floorEntry['lat_lngs'] as $latLng) {
                $this->assertArrayHasKey('lat', $latLng);
                $this->assertArrayHasKey('lng', $latLng);
                $this->assertArrayHasKey('weight', $latLng);
                $this->assertGreaterThanOrEqual(1, $latLng['weight']);
            }
        } finally {
            CombatLogRouteEnemyFailure::whereIn('id', $created)->delete();
        }
    }

    #[Test]
    public function getEnemyFailures_givenNpcIdFilter_returnsOnlyMatchingGridCell(): void
    {
        $created          = [];
        $npcEnemyForcesId = null;

        // Use unlikely npc IDs to avoid collisions with existing test data
        $targetNpcId = 99901;
        $otherNpcId  = 99902;

        try {
            // Arrange — once any npc of the mapping version is worth enemy forces, failures of npcs that are not
            // are filtered out (#4475). findDungeon() shuffles, so without this row the outcome depends on the
            // dungeon picked.
            $npcEnemyForcesId = NpcEnemyForces::query()->create([
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => $targetNpcId,
                'enemy_forces'       => 10,
            ])->id;

            $matching = CombatLogRouteEnemyFailure::create([
                'dungeon_id'         => $this->dungeon->id,
                'floor_id'           => $this->floor->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => $targetNpcId,
                'lat'                => -50.0,
                'lng'                => 100.0,
            ]);
            $created[] = $matching->id;

            $excluded = CombatLogRouteEnemyFailure::create([
                'dungeon_id'         => $this->dungeon->id,
                'floor_id'           => $this->floor->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => $otherNpcId,
                'lat'                => -200.0,
                'lng'                => 300.0,
            ]);
            $created[] = $excluded->id;

            // Act
            $response = $this->get(route('ajax.admin.combatlogroute.enemy_failures', [
                'dungeon_id'         => $this->dungeon->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => [$targetNpcId],
            ]));

            // Assert
            $response->assertOk();

            $body = json_decode($response->content(), true);
            /** @var array<int, array<string, mixed>> $bodyData2 */
            $bodyData2 = $body['data'];
            $latLngs   = collect($bodyData2)->flatMap(fn(array $entry): array => $entry['lat_lngs']);

            $this->assertCount(1, $latLngs);
            $this->assertEquals(1, $latLngs->first()['weight']);
            $this->assertEquals(1, $body['failure_count']);
            $this->assertEquals(1, $body['weight_max']);
        } finally {
            CombatLogRouteEnemyFailure::whereIn('id', $created)->delete();

            if ($npcEnemyForcesId !== null) {
                NpcEnemyForces::query()->whereKey($npcEnemyForcesId)->delete();
            }
        }
    }

    #[Test]
    public function getEnemyFailures_givenNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $nonAdmin = User::factory()->create();

        try {
            $this->assertFalse($nonAdmin->hasRole(Role::ROLE_ADMIN));
            $this->actingAs($nonAdmin);

            // Act
            $response = $this->get(route('ajax.admin.combatlogroute.enemy_failures', [
                'dungeon_id'         => $this->dungeon->id,
                'mapping_version_id' => $this->mappingVersion->id,
            ]));

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $nonAdmin->delete();
        }
    }

    #[Test]
    public function deleteEnemyFailures_givenValidDungeon_deletesRecordsAndReturnsOk(): void
    {
        $created = [];

        try {
            // Arrange
            $failure = CombatLogRouteEnemyFailure::create([
                'dungeon_id'         => $this->dungeon->id,
                'floor_id'           => $this->floor->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => null,
                'lat'                => -50.0,
                'lng'                => 100.0,
            ]);
            $created[] = $failure->id;

            // Act
            $response = $this->delete(route('ajax.admin.combatlogroute.enemy_failures.delete'), [
                'dungeon_id' => $this->dungeon->id,
            ]);

            // Assert
            $response->assertOk();

            $this->assertNull(CombatLogRouteEnemyFailure::find($failure->id));
            $created = [];
        } finally {
            if (!empty($created)) {
                CombatLogRouteEnemyFailure::whereIn('id', $created)->delete();
            }
        }
    }

    #[Test]
    public function deleteEnemyFailures_givenNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $nonAdmin = User::factory()->create();

        try {
            $this->assertFalse($nonAdmin->hasRole(Role::ROLE_ADMIN));
            $this->actingAs($nonAdmin);

            // Act
            $response = $this->delete(route('ajax.admin.combatlogroute.enemy_failures.delete'), [
                'dungeon_id' => $this->dungeon->id,
            ]);

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $nonAdmin->delete();
        }
    }
    #[Test]
    public function getEnemyResolutions_givenNoDungeonId_returnsValidationError(): void
    {
        // Act
        $response = $this->get(route('ajax.admin.combatlogroute.enemy_resolutions'));

        // Assert
        $response->assertUnprocessable();
    }

    #[Test]
    public function getEnemyResolutions_givenNoMappingVersionId_returnsValidationError(): void
    {
        // Act
        $response = $this->get(route('ajax.admin.combatlogroute.enemy_resolutions', [
            'dungeon_id' => $this->dungeon->id,
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('mapping_version_id');
    }

    #[Test]
    public function getEnemyResolutions_givenValidDungeon_returnsFullHeatmapResponseShape(): void
    {
        $created = [];

        try {
            // Arrange - enough matches in one spot for the cell to be drawn at the default minimum
            config(['keystoneguru.enemy_resolution.heatmap_min_samples' => 2]);

            for ($i = 0; $i < 2; $i++) {
                $created[] = $this->createResolution(-50.0 + $i * 0.01, 100.0, 60.0);
            }

            // Act
            $response = $this->get(route('ajax.admin.combatlogroute.enemy_resolutions', [
                'dungeon_id'         => $this->dungeon->id,
                'mapping_version_id' => $this->mappingVersion->id,
            ]));

            // Assert
            $response->assertOk();

            $body = json_decode($response->content(), true);
            $this->assertArrayHasKey('data', $body);
            $this->assertArrayHasKey('data_type', $body);
            $this->assertArrayHasKey('weight_max', $body);
            $this->assertArrayHasKey('resolution_count', $body);
            $this->assertArrayHasKey('drawn_count', $body);
            $this->assertArrayHasKey('metric', $body);
            $this->assertArrayHasKey('min_samples', $body);
            $this->assertArrayHasKey('grid_size_x', $body);
            $this->assertArrayHasKey('grid_size_y', $body);
            $this->assertArrayHasKey('dungeon_routes', $body);

            $this->assertEquals(CombatLogRouteEnemyResolutionHeatmapResult::DATA_TYPE, $body['data_type']);
            // No metric given means the average - the default the form request falls back on
            $this->assertEquals(EnemyResolutionHeatmapMetric::Average->value, $body['metric']);
            $this->assertEquals(2, $body['min_samples']);
            $this->assertGreaterThan(0, $body['grid_size_x']);
            $this->assertGreaterThan(0, $body['grid_size_y']);

            /** @var array<int, array<string, mixed>> $bodyData */
            $bodyData   = $body['data'];
            $floorEntry = collect($bodyData)->firstWhere('floor_id', $this->floor->id);
            $this->assertNotNull($floorEntry);
            $this->assertNotEmpty($floorEntry['lat_lngs']);

            // A cell's weight is a distance in ingame yards rather than a count, unlike the failure heatmap
            foreach ($floorEntry['lat_lngs'] as $latLng) {
                $this->assertArrayHasKey('lat', $latLng);
                $this->assertArrayHasKey('lng', $latLng);
                $this->assertArrayHasKey('weight', $latLng);
                $this->assertArrayHasKey('count', $latLng);
                $this->assertEqualsWithDelta(60.0, $latLng['weight'], 0.01);
            }
        } finally {
            CombatLogRouteEnemyResolution::whereIn('id', $created)->delete();
        }
    }

    #[Test]
    public function getEnemyResolutions_givenNpcIdFilter_returnsOnlyMatchingGridCell(): void
    {
        $created = [];

        // Use unlikely npc IDs to avoid collisions with existing test data
        $targetNpcId = 99903;
        $otherNpcId  = 99904;

        try {
            // Arrange - one cell per npc, each on its own so a single match is enough to be drawn
            config(['keystoneguru.enemy_resolution.heatmap_min_samples' => 1]);

            $created[] = $this->createResolution(-50.0, 100.0, 60.0, npcId: $targetNpcId);
            $created[] = $this->createResolution(-200.0, 300.0, 90.0, npcId: $otherNpcId);

            // Act
            $response = $this->get(route('ajax.admin.combatlogroute.enemy_resolutions', [
                'dungeon_id'         => $this->dungeon->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => [$targetNpcId],
            ]));

            // Assert
            $response->assertOk();

            $body = json_decode($response->content(), true);
            /** @var array<int, array<string, mixed>> $bodyData */
            $bodyData = $body['data'];
            $latLngs  = collect($bodyData)->flatMap(fn(array $entry): array => $entry['lat_lngs']);

            $this->assertCount(1, $latLngs);
            $this->assertEqualsWithDelta(60.0, $latLngs->first()['weight'], 0.01);
            $this->assertEquals(1, $body['resolution_count']);
            $this->assertEquals(1, $body['drawn_count']);
            $this->assertEqualsWithDelta(60.0, $body['weight_max'], 0.01);
        } finally {
            CombatLogRouteEnemyResolution::whereIn('id', $created)->delete();
        }
    }

    #[Test]
    public function getEnemyResolutions_givenUnknownMetric_returnsValidationError(): void
    {
        // Act
        $response = $this->get(route('ajax.admin.combatlogroute.enemy_resolutions', [
            'dungeon_id'         => $this->dungeon->id,
            'mapping_version_id' => $this->mappingVersion->id,
            'metric'             => 'median',
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('metric');
    }

    #[Test]
    public function getEnemyResolutions_givenNegativeMinDistance_returnsValidationError(): void
    {
        // Act
        $response = $this->get(route('ajax.admin.combatlogroute.enemy_resolutions', [
            'dungeon_id'         => $this->dungeon->id,
            'mapping_version_id' => $this->mappingVersion->id,
            'min_distance'       => -1,
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('min_distance');
    }

    #[Test]
    public function getEnemyResolutions_givenNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $nonAdmin = User::factory()->create();

        try {
            $this->assertFalse($nonAdmin->hasRole(Role::ROLE_ADMIN));
            $this->actingAs($nonAdmin);

            // Act
            $response = $this->get(route('ajax.admin.combatlogroute.enemy_resolutions', [
                'dungeon_id'         => $this->dungeon->id,
                'mapping_version_id' => $this->mappingVersion->id,
            ]));

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $nonAdmin->delete();
        }
    }

    #[Test]
    public function deleteEnemyResolutions_givenNoDungeonId_returnsValidationError(): void
    {
        // Act
        $response = $this->delete(route('ajax.admin.combatlogroute.enemy_resolutions.delete'));

        // Assert
        $response->assertUnprocessable();
    }

    #[Test]
    public function deleteEnemyResolutions_givenValidDungeon_deletesOnlyThatDungeonsRecords(): void
    {
        $created = [];

        try {
            // Arrange - a second dungeon's row is what proves the delete is scoped
            $otherDungeon = Dungeon::query()->where('id', '!=', $this->dungeon->id)->firstOrFail();

            $deletedId = $this->createResolution(-50.0, 100.0, 60.0);
            $created[] = $deletedId;

            $keptId    = $this->createResolution(-50.0, 100.0, 60.0, dungeon: $otherDungeon);
            $created[] = $keptId;

            // Act
            $response = $this->delete(route('ajax.admin.combatlogroute.enemy_resolutions.delete'), [
                'dungeon_id' => $this->dungeon->id,
            ]);

            // Assert
            $response->assertOk();
            $this->assertNull(CombatLogRouteEnemyResolution::find($deletedId));
            $this->assertNotNull(CombatLogRouteEnemyResolution::find($keptId));
        } finally {
            CombatLogRouteEnemyResolution::whereIn('id', $created)->delete();
        }
    }

    #[Test]
    public function deleteEnemyResolutions_givenNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $nonAdmin = User::factory()->create();

        try {
            $this->assertFalse($nonAdmin->hasRole(Role::ROLE_ADMIN));
            $this->actingAs($nonAdmin);

            // Act
            $response = $this->delete(route('ajax.admin.combatlogroute.enemy_resolutions.delete'), [
                'dungeon_id' => $this->dungeon->id,
            ]);

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $nonAdmin->delete();
        }
    }

    #[Test]
    public function getEnemyResolutionLines_givenNoDungeonId_returnsValidationError(): void
    {
        // Act
        $response = $this->get(route('ajax.admin.combatlogroute.enemy_resolutions.lines'));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('dungeon_id');
    }

    #[Test]
    public function getEnemyResolutionLines_givenValidDungeon_returnsLinesWithBothEndsAndTheirContext(): void
    {
        $created = [];

        try {
            // Arrange
            $created[] = $this->createResolution(-50.0, 100.0, 60.0, npcId: 99906);

            // Act
            $response = $this->get(route('ajax.admin.combatlogroute.enemy_resolutions.lines', [
                'dungeon_id'         => $this->dungeon->id,
                'mapping_version_id' => $this->mappingVersion->id,
            ]));

            // Assert
            $response->assertOk();
            $response->assertJsonStructure([
                'data' => [[
                    'floor_id',
                    'lat',
                    'lng',
                    'enemy_lat',
                    'enemy_lng',
                    'distance',
                    'weighted_distance',
                    'npc_id',
                    'npc_name',
                    'enemy_id',
                    'source',
                    'dungeon_route_id',
                    'dungeon_route_public_key',
                    'dungeon_route_url',
                ]],
            ]);

            $this->assertCount(1, $response->json('data'));
            $this->assertSame($this->floor->id, $response->json('data.0.floor_id'));
            $this->assertSame(99906, $response->json('data.0.npc_id'));
            $this->assertEqualsWithDelta(-50.0, $response->json('data.0.lat'), 0.01);
            $this->assertEqualsWithDelta(-49.0, $response->json('data.0.enemy_lat'), 0.01);
            $this->assertEqualsWithDelta(60.0, $response->json('data.0.weighted_distance'), 0.01);
        } finally {
            CombatLogRouteEnemyResolution::whereIn('id', $created)->delete();
        }
    }

    #[Test]
    public function getEnemyResolutionLines_givenLimitAboveTheMaximum_returnsValidationError(): void
    {
        // Act
        $response = $this->get(route('ajax.admin.combatlogroute.enemy_resolutions.lines', [
            'dungeon_id'         => $this->dungeon->id,
            'mapping_version_id' => $this->mappingVersion->id,
            'limit'              => AjaxAdminCombatLogRouteGetEnemyResolutionLinesFormRequest::LIMIT_MAX + 1,
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('limit');
    }

    #[Test]
    public function getEnemyResolutionLines_givenNegativeMinDistance_returnsValidationError(): void
    {
        // Act
        $response = $this->get(route('ajax.admin.combatlogroute.enemy_resolutions.lines', [
            'dungeon_id'         => $this->dungeon->id,
            'mapping_version_id' => $this->mappingVersion->id,
            'min_distance'       => -1,
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('min_distance');
    }

    #[Test]
    public function getEnemyResolutionLines_givenNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $nonAdmin = User::factory()->create();

        try {
            $this->assertFalse($nonAdmin->hasRole(Role::ROLE_ADMIN));
            $this->actingAs($nonAdmin);

            // Act
            $response = $this->get(route('ajax.admin.combatlogroute.enemy_resolutions.lines', [
                'dungeon_id'         => $this->dungeon->id,
                'mapping_version_id' => $this->mappingVersion->id,
            ]));

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $nonAdmin->delete();
        }
    }

    /**
     * @return int The id of the created record
     */
    private function createResolution(float $lat, float $lng, float $weightedDistance, ?int $npcId = null, ?Dungeon $dungeon = null): int
    {
        $dungeon ??= $this->dungeon;
        $isOwnDungeon = $dungeon->id === $this->dungeon->id;

        /** @var Floor $floor */
        $floor = $isOwnDungeon ? $this->floor : $dungeon->floors()->firstOrFail();

        return CombatLogRouteEnemyResolution::create([
            'dungeon_id'         => $dungeon->id,
            'floor_id'           => $floor->id,
            'mapping_version_id' => $isOwnDungeon ? $this->mappingVersion->id : $dungeon->getCurrentMappingVersion()->id,
            'npc_id'             => $npcId,
            // enemy_id is NOT NULL but carries no foreign key - any enemy the row claims to have resolved to does
            'enemy_id'          => 1,
            'lat'               => $lat,
            'lng'               => $lng,
            'enemy_lat'         => $lat + 1,
            'enemy_lng'         => $lng + 1,
            'distance'          => $weightedDistance,
            'weighted_distance' => $weightedDistance,
        ])->id;
    }
}
