<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\CombatLog\Dtos\CombatLogRouteEnemyFailureHeatmapResult;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('AjaxAdminCombatLogRoute')]
final class AjaxAdminCombatLogRouteControllerTest extends AjaxPublicTestCase
{
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

        /** @var Dungeon $dungeon */
        $dungeon       = Dungeon::inRandomOrder()->first();
        $this->dungeon = $dungeon;

        /** @var Floor $floor */
        $floor       = $this->dungeon->floors()->where('facade', 0)->first();
        $this->floor = $floor;

        $this->mappingVersion = $this->dungeon->getCurrentMappingVersion();
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
                'dungeon_id' => $this->dungeon->id,
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
        $created = [];

        // Use unlikely npc IDs to avoid collisions with existing test data
        $targetNpcId = 99901;
        $otherNpcId  = 99902;

        try {
            // Arrange
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
                'dungeon_id' => $this->dungeon->id,
                'npc_id'     => [$targetNpcId],
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
                'dungeon_id' => $this->dungeon->id,
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
}
