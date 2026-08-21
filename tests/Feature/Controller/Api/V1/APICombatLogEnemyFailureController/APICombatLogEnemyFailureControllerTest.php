<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogEnemyFailureController;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
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
#[Group('APICombatLogEnemyFailure')]
final class APICombatLogEnemyFailureControllerTest extends PublicTestCase
{
    use ProvidesDungeon;

    private Dungeon $dungeon;

    private Floor $floor;

    private MappingVersion $mappingVersion;

    /** @var array<int, int> */
    private array $createdFailureIds = [];

    /** @var array<int, int> */
    private array $createdDungeonRouteIds = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        [$this->dungeon, $this->mappingVersion] = $this->findDungeon(facadeEnabled: false);

        /** @var Floor $floor */
        $floor       = $this->dungeon->floors()->where('facade', 0)->firstOrFail();
        $this->floor = $floor;
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            CombatLogRouteEnemyFailure::query()->whereIn('id', $this->createdFailureIds)->delete();
            DungeonRoute::query()->whereIn('id', $this->createdDungeonRouteIds)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function index_givenAdmin_returnsRowsWithPublicKey(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $this->dungeon->id,
            'mapping_version_id' => $this->mappingVersion->id,
        ]);
        $this->createdDungeonRouteIds[] = $dungeonRoute->id;

        $failure = $this->createFailure(['dungeon_route_id' => $dungeonRoute->id, 'npc_id' => 99801]);

        // Act
        $response = $this->getJson(route('api.v1.combatlog.enemy_failures.index', ['dungeon' => $this->dungeon->slug]));

        // Assert
        $response->assertOk();
        $response->assertJsonStructure(['data' => [['id', 'dungeon_id', 'floor_id', 'mapping_version_id', 'npc_id', 'dungeon_route_id', 'dungeon_route_public_key', 'lat', 'lng', 'created_at']], 'meta' => ['count', 'next_after_id', 'has_more']]);

        /** @var array<int, array<string, mixed>> $data */
        $data = $response->json('data');
        $row  = collect($data)->firstWhere('id', $failure->id);
        $this->assertNotNull($row);
        $this->assertSame($dungeonRoute->public_key, $row['dungeon_route_public_key']);
        $this->assertSame(99801, $row['npc_id']);
        $this->assertFalse($response->json('meta.has_more'));
        $this->assertNull($response->json('meta.next_after_id'));
    }

    #[Test]
    public function index_givenAfterIdAndLimit_returnsOnlyNewerRowsAndNextAfterId(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $first  = $this->createFailure();
        $second = $this->createFailure();
        $third  = $this->createFailure();

        // Act — page of 1 after the first row
        $response = $this->getJson(route('api.v1.combatlog.enemy_failures.index', [
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
        $response = $this->getJson(route('api.v1.combatlog.enemy_failures.index', [
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

        $matching = $this->createFailure(['npc_id' => 99811]);
        $this->createFailure(['npc_id' => 99812]);
        $this->createFailure(['npc_id' => 99811, 'mapping_version_id' => PHP_INT_MAX]);

        // Act
        $response = $this->getJson(route('api.v1.combatlog.enemy_failures.index', [
            'dungeon'            => $this->dungeon->slug,
            'npc_id'             => [99811],
            'mapping_version_id' => $this->mappingVersion->id,
        ]));

        // Assert
        $response->assertOk();
        $this->assertSame([$matching->id], array_column($response->json('data'), 'id'));
    }

    #[Test]
    public function index_givenLimitAboveMax_returnsUnprocessable(): void
    {
        // Arrange
        $this->actingAsAdmin();

        // Act
        $response = $this->getJson(route('api.v1.combatlog.enemy_failures.index', [
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
        $response = $this->getJson(route('api.v1.combatlog.enemy_failures.index', ['dungeon' => 'no-such-dungeon-slug']));

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
            $response = $this->getJson(route('api.v1.combatlog.enemy_failures.index', ['dungeon' => $this->dungeon->slug]));

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
            $response = $this->getJson(route('api.v1.combatlog.enemy_failures.index', ['dungeon' => $this->dungeon->slug]));

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
     * @param array<string, mixed> $attributes
     */
    private function createFailure(array $attributes = []): CombatLogRouteEnemyFailure
    {
        $failure = CombatLogRouteEnemyFailure::create(array_merge([
            'dungeon_id'         => $this->dungeon->id,
            'floor_id'           => $this->floor->id,
            'mapping_version_id' => $this->mappingVersion->id,
            'npc_id'             => null,
            'lat'                => -50.0,
            'lng'                => 100.0,
        ], $attributes));

        $this->createdFailureIds[] = $failure->id;

        return $failure;
    }
}
