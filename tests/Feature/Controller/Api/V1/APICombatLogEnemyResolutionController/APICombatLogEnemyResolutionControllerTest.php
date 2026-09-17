<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogEnemyResolutionController;

use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('API')]
#[Group('CombatLog')]
#[Group('APICombatLogEnemyResolution')]
final class APICombatLogEnemyResolutionControllerTest extends PublicTestCase
{
    use ProvidesDungeon;

    private Dungeon $dungeon;

    private Floor $floor;

    private MappingVersion $mappingVersion;

    private Enemy $enemy;

    /** @var array<int, int> */
    private array $createdResolutionIds = [];

    /** @var array<int, int> */
    private array $createdDungeonRouteIds = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        /** @var Enemy $enemy */
        [$this->dungeon, $this->mappingVersion, $enemy] = $this->findDungeon(
            facadeEnabled: false,
            resolve:       static fn(Dungeon $dungeon, MappingVersion $mappingVersion) => $mappingVersion->enemies()
                ->whereIn('floor_id', $dungeon->floors()->where('facade', 0)->select('id'))
                ->first(),
        );
        $this->enemy = $enemy;

        /** @var Floor $floor */
        $floor       = $this->dungeon->floors()->where('id', $enemy->floor_id)->firstOrFail();
        $this->floor = $floor;
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            CombatLogRouteEnemyResolution::query()->whereIn('id', $this->createdResolutionIds)->delete();
            DungeonRoute::query()->whereIn('id', $this->createdDungeonRouteIds)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function index_givenAdmin_returnsRowsWithPublicKeyAndEnemyColumns(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $this->dungeon->id,
            'mapping_version_id' => $this->mappingVersion->id,
        ]);
        $this->createdDungeonRouteIds[] = $dungeonRoute->id;

        $resolution = $this->createResolution(['dungeon_route_id' => $dungeonRoute->id, 'npc_id' => 99801]);

        // Act
        $response = $this->getJson(route('api.v1.combatlog.enemy_resolutions.index', [
            'dungeon'  => $this->dungeon->slug,
            'after_id' => $resolution->id - 1,
        ]));

        // Assert
        $response->assertOk();
        $response->assertJsonStructure(['data' => [['id', 'dungeon_id', 'floor_id', 'mapping_version_id', 'npc_id', 'enemy_id', 'dungeon_route_id', 'dungeon_route_public_key', 'lat', 'lng', 'enemy_lat', 'enemy_lng', 'distance', 'weighted_distance', 'created_at']], 'meta' => ['count', 'next_after_id', 'has_more']]);

        /** @var array<int, array<string, mixed>> $data */
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $row = collect($data)->firstWhere('id', $resolution->id);
        $this->assertNotNull($row);
        $this->assertSame($dungeonRoute->public_key, $row['dungeon_route_public_key']);
        $this->assertSame(99801, $row['npc_id']);
        $this->assertSame($this->enemy->id, $row['enemy_id']);
        $this->assertSame(-60.25, $row['enemy_lat']);
        $this->assertSame(110.5, $row['enemy_lng']);
        $this->assertSame(42.5, $row['distance']);
        $this->assertSame(42.5, $row['weighted_distance']);
        $this->assertFalse($response->json('meta.has_more'));
        $this->assertNull($response->json('meta.next_after_id'));
    }

    #[Test]
    public function index_givenAfterIdAndLimit_returnsOnlyNewerRowsAndNextAfterId(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $first  = $this->createResolution();
        $second = $this->createResolution();
        $third  = $this->createResolution();

        // Act — page of 1 after the first row
        $response = $this->getJson(route('api.v1.combatlog.enemy_resolutions.index', [
            'dungeon'  => $this->dungeon->slug,
            'after_id' => $first->id,
            'limit'    => 1,
        ]));

        // Assert — only the second row, and a cursor pointing at it because the third still follows
        $response->assertOk();
        $this->assertSame([$second->id], array_column($response->json('data'), 'id'));
        $this->assertSame(1, $response->json('meta.count'));
        $this->assertTrue($response->json('meta.has_more'));
        $this->assertSame($second->id, $response->json('meta.next_after_id'));

        // Act — next page
        $response = $this->getJson(route('api.v1.combatlog.enemy_resolutions.index', [
            'dungeon'  => $this->dungeon->slug,
            'after_id' => $response->json('meta.next_after_id'),
            'limit'    => 1,
        ]));

        // Assert — the third row, last page
        $this->assertSame([$third->id], array_column($response->json('data'), 'id'));
        $this->assertFalse($response->json('meta.has_more'));
    }

    #[Test]
    public function index_givenNpcIdAndMappingVersionFilters_returnsOnlyMatching(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $matching = $this->createResolution(['npc_id' => 99811]);
        $this->createResolution(['npc_id' => 99812]);
        $this->createResolution(['npc_id' => 99811, 'mapping_version_id' => PHP_INT_MAX]);

        // Act
        $response = $this->getJson(route('api.v1.combatlog.enemy_resolutions.index', [
            'dungeon'            => $this->dungeon->slug,
            'npc_id'             => [99811],
            'mapping_version_id' => $this->mappingVersion->id,
        ]));

        // Assert
        $response->assertOk();
        $this->assertSame([$matching->id], array_column($response->json('data'), 'id'));
    }

    #[Test]
    public function index_givenMinDistance_returnsOnlyRowsAtLeastThatFarOff(): void
    {
        // Arrange — one row below the threshold and one above it, judged on weighted_distance
        $this->actingAsAdmin();

        $this->createResolution(['distance' => 90.0, 'weighted_distance' => 19.5]);
        $far = $this->createResolution(['distance' => 10.0, 'weighted_distance' => 20.5]);

        // Act
        $response = $this->getJson(route('api.v1.combatlog.enemy_resolutions.index', [
            'dungeon'      => $this->dungeon->slug,
            'min_distance' => 20.0,
        ]));

        // Assert
        $response->assertOk();
        $this->assertSame([$far->id], array_column($response->json('data'), 'id'));
    }

    #[Test]
    public function index_givenNegativeMinDistance_returnsUnprocessable(): void
    {
        // Arrange
        $this->actingAsAdmin();

        // Act
        $response = $this->getJson(route('api.v1.combatlog.enemy_resolutions.index', [
            'dungeon'      => $this->dungeon->slug,
            'min_distance' => -1,
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('success', false);
    }

    #[Test]
    public function index_givenLimitAboveMax_returnsUnprocessable(): void
    {
        // Arrange
        $this->actingAsAdmin();

        // Act
        $response = $this->getJson(route('api.v1.combatlog.enemy_resolutions.index', [
            'dungeon' => $this->dungeon->slug,
            'limit'   => 1001,
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('success', false);
    }

    #[Test]
    public function index_givenUnknownDungeon_returnsNotFound(): void
    {
        // Arrange
        $this->actingAsAdmin();

        // Act
        $response = $this->getJson(route('api.v1.combatlog.enemy_resolutions.index', ['dungeon' => 'no-such-dungeon-slug']));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function index_givenAiAgent_returnsOk(): void
    {
        // Arrange
        /** @var User $aiAgent */
        $aiAgent = User::factory()->create();

        try {
            $aiAgent->addRole(Role::ROLE_AI_AGENT);
            $this->actingAs($aiAgent);

            // Act
            $response = $this->getJson(route('api.v1.combatlog.enemy_resolutions.index', ['dungeon' => $this->dungeon->slug]));

            // Assert
            $response->assertOk();
        } finally {
            $aiAgent->delete();
        }
    }

    #[Test]
    public function index_givenAuthenticatedNonAdmin_returnsForbidden(): void
    {
        // Arrange
        /** @var User $nonAdmin */
        $nonAdmin = User::factory()->create();

        try {
            $this->actingAs($nonAdmin);

            // Act
            $response = $this->getJson(route('api.v1.combatlog.enemy_resolutions.index', ['dungeon' => $this->dungeon->slug]));

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $nonAdmin->delete();
        }
    }

    private function actingAsAdmin(): void
    {
        /** @var User $admin */
        $admin = User::findOrFail(1);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must have the admin role for this test (seed the database).');
        $this->actingAs($admin);
    }

    /**
     * An imported row keeps the dungeon_route_id it had on the deployment it came from, which identifies a completely
     * different route here as soon as the two numbers collide. Resolving it against a local route would hand out a
     * public key pointing at someone else's route, so an imported row gets no public key at all.
     */
    #[Test]
    public function index_givenImportedRowPointingAtALocalRouteId_returnsNoPublicKeyForIt(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $localRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $this->dungeon->id,
            'mapping_version_id' => $this->mappingVersion->id,
        ]);
        $this->createdDungeonRouteIds[] = $localRoute->id;

        // The imported row's remote id happens to be this local route's id - the collision this guards against
        $imported = $this->createResolution([
            'dungeon_route_id' => $localRoute->id,
            'source'           => 'production',
        ]);

        // Act
        $response = $this->getJson(route('api.v1.combatlog.enemy_resolutions.index', [
            'dungeon'  => $this->dungeon->slug,
            'after_id' => $imported->id - 1,
        ]));

        // Assert
        $response->assertOk();

        /** @var array<int, array<string, mixed>> $data */
        $data = $response->json('data');
        $row  = collect($data)->firstWhere('id', $imported->id);
        $this->assertNotNull($row);
        $this->assertSame($localRoute->id, $row['dungeon_route_id']);
        $this->assertNull($row['dungeon_route_public_key']);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createResolution(array $attributes = []): CombatLogRouteEnemyResolution
    {
        $resolution = CombatLogRouteEnemyResolution::create(array_merge([
            'dungeon_id'         => $this->dungeon->id,
            'floor_id'           => $this->floor->id,
            'mapping_version_id' => $this->mappingVersion->id,
            'npc_id'             => null,
            'enemy_id'           => $this->enemy->id,
            'lat'                => -50.0,
            'lng'                => 100.0,
            'enemy_lat'          => -60.25,
            'enemy_lng'          => 110.5,
            'distance'           => 42.5,
            'weighted_distance'  => 42.5,
        ], $attributes));

        $this->createdResolutionIds[] = $resolution->id;

        return $resolution;
    }
}
