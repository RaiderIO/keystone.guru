<?php

namespace Tests\Feature\Controller\Api\V1\APIWalkGraphController;

use App\Logic\WalkGraph\WalkGrid;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Laratrust\Role;
use App\Models\User;
use App\Models\WalkGraph\WalkGraph;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('API')]
#[Group('WalkGraph')]
#[Group('APIWalkGraph')]
final class APIWalkGraphControllerTest extends PublicTestCase
{
    private ?WalkGraph $walkGraph = null;

    private ?Floor $floor = null;

    private ?User $user = null;

    #[Override]
    protected function tearDown(): void
    {
        try {
            $this->walkGraph?->delete();
            $this->floor?->delete();
            $this->user?->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function index_givenAdmin_returnsGraphMetadataWithTheDungeonFloorsUsingItsUiMap(): void
    {
        // Arrange
        $this->actingAsAdmin();
        $dungeon = $this->createWalkGraphWithFloor(['run_count' => 37, 'node_count' => 1200, 'edge_count' => 2400, 'largest_component_node_count' => 1100]);

        // Act
        $response = $this->getJson(route('api.v1.walkgraphs.index'));

        // Assert
        $response->assertOk();
        /** @var array<int, array<string, mixed>> $data */
        $data  = $response->json('data');
        $entry = collect($data)->firstWhere('ui_map_id', $this->walkGraph->ui_map_id);
        $this->assertNotNull($entry, 'The graph is listed');
        $this->assertSame(37, $entry['run_count']);
        $this->assertSame(1200, $entry['node_count']);
        $this->assertSame(2400, $entry['edge_count']);
        $this->assertSame(1100, $entry['largest_component_node_count']);
        $this->assertArrayNotHasKey('edges', $entry, 'The index never carries the edge blob');
        $this->assertCount(1, $entry['dungeons']);
        $this->assertSame($dungeon->key, $entry['dungeons'][0]['key']);
        $this->assertSame($dungeon->expansion->shortname, $entry['dungeons'][0]['expansion']);
        $this->assertSame([$this->floor->id], array_column($entry['dungeons'][0]['floors'], 'id'));
    }

    #[Test]
    public function index_givenAiAgent_returnsOk(): void
    {
        // Arrange
        $this->actingAsRole(Role::ROLE_AI_AGENT);
        $this->createWalkGraphWithFloor();

        // Act
        $response = $this->getJson(route('api.v1.walkgraphs.index'));

        // Assert
        $response->assertOk();
        $this->assertContains($this->walkGraph->ui_map_id, array_column($response->json('data'), 'ui_map_id'));
    }

    #[Test]
    public function index_givenRegularUser_returnsForbidden(): void
    {
        // Arrange
        $this->actingAsRole(null);
        $this->createWalkGraphWithFloor();

        // Act
        $response = $this->getJson(route('api.v1.walkgraphs.index'));

        // Assert
        $response->assertForbidden();
    }

    #[Test]
    public function show_givenKnownUiMap_returnsBase64EdgesThatDecodeToTheStoredCounts(): void
    {
        // Arrange
        $this->actingAsAdmin();
        $edgeCounts = [
            WalkGrid::edgeKey(0, 0, 1, 0) => 5,
            WalkGrid::edgeKey(1, 0, 1, 1) => 2,
        ];
        $dungeon = $this->createWalkGraphWithFloor([
            'cell_size' => 2.5,
            'edges'     => WalkGrid::encodeEdgeCounts($edgeCounts),
        ]);

        // Act
        $response = $this->getJson(route('api.v1.walkgraphs.show', ['uiMapId' => $this->walkGraph->ui_map_id]));

        // Assert
        $response->assertOk();
        $this->assertSame($this->walkGraph->ui_map_id, $response->json('data.ui_map_id'));
        $this->assertEquals(2.5, $response->json('data.cell_size'));
        $this->assertSame($dungeon->key, $response->json('data.dungeons.0.key'));
        $decoded = base64_decode((string)$response->json('data.edges'), true);
        $this->assertIsString($decoded);
        $this->assertEquals($edgeCounts, WalkGrid::decodeEdgeCounts($decoded));
    }

    #[Test]
    public function show_givenUnknownUiMap_returnsNotFound(): void
    {
        // Arrange
        $this->actingAsAdmin();
        $this->createWalkGraphWithFloor();
        $knownUiMapId   = $this->walkGraph->ui_map_id;
        $unknownUiMapId = $knownUiMapId + 1;
        WalkGraph::query()->where('ui_map_id', $unknownUiMapId)->delete();

        // Act
        $knownResponse   = $this->getJson(route('api.v1.walkgraphs.show', ['uiMapId' => $knownUiMapId]));
        $unknownResponse = $this->getJson(route('api.v1.walkgraphs.show', ['uiMapId' => $unknownUiMapId]));

        // Assert
        $knownResponse->assertOk();
        $unknownResponse->assertNotFound();
    }

    #[Test]
    public function show_givenRegularUser_returnsForbidden(): void
    {
        // Arrange
        $this->actingAsRole(null);
        $this->createWalkGraphWithFloor();

        // Act
        $response = $this->getJson(route('api.v1.walkgraphs.show', ['uiMapId' => $this->walkGraph->ui_map_id]));

        // Assert
        $response->assertForbidden();
    }

    /**
     * Creates a graph on an unused ui map, plus a floor in a seeded dungeon that uses it.
     *
     * @param array<string, mixed> $attributes
     */
    private function createWalkGraphWithFloor(array $attributes = []): Dungeon
    {
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::query()->with('expansion')->whereHas('floors')->firstOrFail();

        $this->walkGraph = WalkGraph::factory()->create($attributes);
        $this->floor     = Floor::create([
            'dungeon_id'   => $dungeon->id,
            'index'        => 99,
            'ui_map_id'    => $this->walkGraph->ui_map_id,
            'name'         => 'Test walk graph floor',
            'ingame_min_x' => 0,
            'ingame_min_y' => 0,
            'ingame_max_x' => 1000,
            'ingame_max_y' => 1000,
        ]);

        return $dungeon;
    }

    private function actingAsAdmin(): void
    {
        /** @var User $admin */
        $admin = User::findOrFail(1);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must have the admin role for this test (seed the database).');
        $this->actingAs($admin);
    }

    private function actingAsRole(?string $role): void
    {
        $this->user = User::factory()->create();
        if ($role !== null) {
            $this->user->addRole($role);
        }
        $this->actingAs($this->user);
    }
}
