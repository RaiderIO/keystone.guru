<?php

namespace Tests\Feature\Controller\Ajax;

use App\Features\WalkGraphLayer;
use App\Logic\WalkGraph\WalkGrid;
use App\Models\Feature\Feature;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Models\WalkGraph\WalkGraph;
use Database\Factories\FloorFactory;
use Laravel\Pennant\Feature as Pennant;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('WalkGraph')]
final class AjaxWalkGraphControllerTest extends PublicTestCase
{
    use CreatesDungeon;

    private const int UI_MAP_ID = 9_990_502;

    private ?string $originalAdminValue = null;

    private bool $adminRowExisted = false;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $adminRow                 = $this->findAdminRow();
        $this->adminRowExisted    = $adminRow !== null;
        $this->originalAdminValue = $adminRow?->value;

        $this->defaultHeaders = [
            'X-Requested-With' => 'XMLHttpRequest',
        ];

        WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->delete();
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->delete();

            if ($this->adminRowExisted) {
                $this->setAdminValue($this->originalAdminValue === 'true');
            } else {
                $this->findAdminRow()?->delete();
            }
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function show_givenAdminAndFloorWithGraph_returnsSegments(): void
    {
        // Arrange
        $this->setAdminValue(true);
        $admin = $this->createUser(Role::ROLE_ADMIN);
        $floor = $this->createFloor();
        WalkGraph::factory()->create([
            'ui_map_id' => self::UI_MAP_ID,
            'run_count' => 20,
            'edges'     => WalkGrid::encodeEdgeCounts([
                WalkGrid::edgeKey(10, 10, 11, 10) => 3,
                WalkGrid::edgeKey(11, 10, 12, 10) => 3,
            ]),
        ]);

        try {
            $this->be($admin);

            // Act
            $response = $this->get(route('ajax.floor.walkgraph', ['floor' => $floor->id]));

            // Assert
            $response->assertOk();
            $response->assertJsonPath('run_count', 20);
            $response->assertJsonPath('max_run_count', 3);
            $response->assertJsonCount(1, 'segments');
            $response->assertJsonPath('segments.0.0', 3);
        } finally {
            $this->deleteUser($admin);
        }
    }

    #[Test]
    public function show_givenAdminAndFloorWithoutGraph_returnsNoSegments(): void
    {
        // Arrange
        $this->setAdminValue(true);
        $admin = $this->createUser(Role::ROLE_ADMIN);
        $floor = $this->createFloor();

        try {
            $this->be($admin);

            // Act
            $response = $this->get(route('ajax.floor.walkgraph', ['floor' => $floor->id]));

            // Assert
            $response->assertOk();
            $response->assertJsonPath('run_count', 0);
            $response->assertJsonCount(0, 'segments');
        } finally {
            $this->deleteUser($admin);
        }
    }

    #[Test]
    public function show_givenNonAdmin_returnsNotFound(): void
    {
        // Arrange
        $this->setAdminValue(true);
        $user  = $this->createUser(Role::ROLE_USER);
        $floor = $this->createFloor();

        try {
            $this->be($user);

            // Act
            $response = $this->get(route('ajax.floor.walkgraph', ['floor' => $floor->id]));

            // Assert
            $response->assertNotFound();
        } finally {
            $this->deleteUser($user);
        }
    }

    #[Test]
    public function show_givenAdminSwitchOff_returnsNotFound(): void
    {
        // Arrange
        $this->setAdminValue(false);
        $admin = $this->createUser(Role::ROLE_ADMIN);
        $floor = $this->createFloor();

        try {
            $this->be($admin);

            // Act
            $response = $this->get(route('ajax.floor.walkgraph', ['floor' => $floor->id]));

            // Assert
            $response->assertNotFound();
        } finally {
            $this->deleteUser($admin);
        }
    }

    #[Test]
    public function show_givenGuest_returnsNotFound(): void
    {
        // Arrange
        $this->setAdminValue(true);
        $floor = $this->createFloor();

        try {
            // Act
            $response = $this->get(route('ajax.floor.walkgraph', ['floor' => $floor->id]));

            // Assert
            $response->assertNotFound();
        } finally {
            Pennant::for(null)->forget(WalkGraphLayer::class);
        }
    }

    #[Test]
    public function area_givenAdminAndFloorWithGraph_returnsRings(): void
    {
        // Arrange
        $this->setAdminValue(true);
        $admin = $this->createUser(Role::ROLE_ADMIN);
        $floor = $this->createFloor();
        WalkGraph::factory()->create([
            'ui_map_id' => self::UI_MAP_ID,
            'run_count' => 20,
            'edges'     => WalkGrid::encodeEdgeCounts([
                WalkGrid::edgeKey(10, 10, 11, 10) => 3,
                WalkGrid::edgeKey(11, 10, 12, 10) => 3,
            ]),
        ]);

        try {
            $this->be($admin);

            // Act
            $response = $this->get(route('ajax.floor.walkgraph.area', ['floor' => $floor->id]));

            // Assert
            $response->assertOk();
            $response->assertJsonStructure(['run_count', 'min_runs', 'rings']);
            $response->assertJsonMissingPath('segments');
            $response->assertJsonPath('run_count', 20);
            $response->assertJsonCount(1, 'rings');
        } finally {
            $this->deleteUser($admin);
        }
    }

    #[Test]
    public function area_givenNonAdmin_returnsNotFound(): void
    {
        // Arrange
        $this->setAdminValue(true);
        $user  = $this->createUser(Role::ROLE_USER);
        $floor = $this->createFloor();

        try {
            $this->be($user);

            // Act
            $response = $this->get(route('ajax.floor.walkgraph.area', ['floor' => $floor->id]));

            // Assert
            $response->assertNotFound();
        } finally {
            $this->deleteUser($user);
        }
    }

    #[Test]
    public function area_givenAdminAndFacadeFloorOverAGraphedFloor_returnsRingsInFacadeCoordinates(): void
    {
        // Arrange - the floor union shrinks the whole floor into an 80 wide square around its centre on the facade
        $this->setAdminValue(true);
        $admin                                  = $this->createUser(Role::ROLE_ADMIN);
        [$mappingVersion, $facadeFloor, $floor] = $this->createFacade();
        $this->createGraph();

        try {
            $this->be($admin);
            $floorRings = $this->get(route('ajax.floor.walkgraph.area', ['floor' => $floor->id]))->json('rings');

            // Act
            $response = $this->get(route('ajax.floor.walkgraph.area', [
                'floor'              => $facadeFloor->id,
                'mapping_version_id' => $mappingVersion->id,
            ]));

            // Assert
            $response->assertOk();
            $response->assertJsonPath('run_count', 20);
            $response->assertJsonCount(1, 'rings');
            $this->assertNotEquals($floorRings, $response->json('rings'));
            $this->assertRingIsAroundTheFloorUnion($response->json('rings.0'));
        } finally {
            $this->deleteUser($admin);
        }
    }

    #[Test]
    public function area_givenAdminAndFacadeFloorWithoutMappingVersion_usesTheDungeonsCurrentMappingVersion(): void
    {
        // Arrange
        $this->setAdminValue(true);
        $admin           = $this->createUser(Role::ROLE_ADMIN);
        [, $facadeFloor] = $this->createFacade();
        $this->createGraph();

        try {
            $this->be($admin);

            // Act
            $response = $this->get(route('ajax.floor.walkgraph.area', ['floor' => $facadeFloor->id]));

            // Assert
            $response->assertOk();
            $response->assertJsonCount(1, 'rings');
            $this->assertRingIsAroundTheFloorUnion($response->json('rings.0'));
        } finally {
            $this->deleteUser($admin);
        }
    }

    #[Test]
    public function area_givenAdminAndFacadeFloorWithoutGraphedFloors_returnsNoRings(): void
    {
        // Arrange
        $this->setAdminValue(true);
        $admin                          = $this->createUser(Role::ROLE_ADMIN);
        [$mappingVersion, $facadeFloor] = $this->createFacade();

        try {
            $this->be($admin);

            // Act
            $response = $this->get(route('ajax.floor.walkgraph.area', [
                'floor'              => $facadeFloor->id,
                'mapping_version_id' => $mappingVersion->id,
            ]));

            // Assert
            $response->assertOk();
            $response->assertJsonPath('run_count', 0);
            $response->assertJsonCount(0, 'rings');
        } finally {
            $this->deleteUser($admin);
        }
    }

    #[Test]
    public function area_givenMappingVersionOfAnotherDungeon_returnsUnprocessable(): void
    {
        // Arrange
        $this->setAdminValue(true);
        $admin           = $this->createUser(Role::ROLE_ADMIN);
        [, $facadeFloor] = $this->createFacade();
        /** @var MappingVersion $otherMappingVersion */
        $otherMappingVersion = $this->createDungeon()->mappingVersions()->firstOrFail();

        try {
            $this->be($admin);

            // Act
            $response = $this->getJson(route('ajax.floor.walkgraph.area', [
                'floor'              => $facadeFloor->id,
                'mapping_version_id' => $otherMappingVersion->id,
            ]));

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['mapping_version_id']);
        } finally {
            $this->deleteUser($admin);
        }
    }

    #[Test]
    public function show_givenAdminAndFacadeFloorOnAGraphedUiMap_returnsNoSegments(): void
    {
        // Arrange - the facade floor itself sits on the graphed ui map, so only its being a facade keeps it empty
        $this->setAdminValue(true);
        $admin           = $this->createUser(Role::ROLE_ADMIN);
        [, $facadeFloor] = $this->createFacade();
        $facadeFloor->update([
            'ui_map_id'    => self::UI_MAP_ID,
            'ingame_min_x' => 0,
            'ingame_max_x' => 300,
            'ingame_min_y' => 0,
            'ingame_max_y' => 300,
        ]);
        $this->createGraph();

        try {
            $this->be($admin);

            // Act
            $response = $this->get(route('ajax.floor.walkgraph', ['floor' => $facadeFloor->id]));

            // Assert
            $response->assertOk();
            $response->assertJsonPath('run_count', 0);
            $response->assertJsonCount(0, 'segments');
        } finally {
            $this->deleteUser($admin);
        }
    }

    /**
     * A facade-enabled dungeon whose floor sits on the facade through one floor union, 80 wide around lat -100,
     * lng 150.
     *
     * @return array{MappingVersion, Floor, Floor} the mapping version, the facade floor and the floor below it
     */
    private function createFacade(): array
    {
        $dungeon = $this->createDungeon(mappingVersionAttributes: ['facade_enabled' => true]);
        /** @var Floor $floor */
        $floor = $dungeon->floors()->firstOrFail();
        $floor->update([
            'ui_map_id'    => self::UI_MAP_ID,
            'ingame_min_x' => 0,
            'ingame_max_x' => 300,
            'ingame_min_y' => 0,
            'ingame_max_y' => 300,
        ]);

        $facadeFloor = FloorFactory::new()->create([
            'dungeon_id'   => $dungeon->id,
            'index'        => 99,
            'name'         => 'Test Facade Floor',
            'facade'       => true,
            'ui_map_id'    => null,
            'ingame_min_x' => 0,
            'ingame_max_x' => 0,
            'ingame_min_y' => 0,
            'ingame_max_y' => 0,
        ]);

        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $dungeon->mappingVersions()->firstOrFail();
        FloorUnion::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $facadeFloor->id,
            'target_floor_id'    => $floor->id,
            'lat'                => -100,
            'lng'                => 150,
            'size'               => 80,
            'rotation'           => 0,
        ]);

        return [$mappingVersion, $facadeFloor, $floor];
    }

    private function createGraph(): void
    {
        WalkGraph::factory()->create([
            'ui_map_id' => self::UI_MAP_ID,
            'run_count' => 20,
            'edges'     => WalkGrid::encodeEdgeCounts([
                WalkGrid::edgeKey(10, 10, 11, 10) => 3,
                WalkGrid::edgeKey(11, 10, 12, 10) => 3,
            ]),
        ]);
    }

    /**
     * @param list<array{float, float}> $ring [lat, lng]
     */
    private function assertRingIsAroundTheFloorUnion(array $ring): void
    {
        foreach ($ring as [$lat, $lng]) {
            $this->assertEqualsWithDelta(-100, $lat, 60);
            $this->assertEqualsWithDelta(150, $lng, 60);
        }
    }

    private function createFloor(): Floor
    {
        /** @var Floor $floor */
        $floor = $this->createDungeon()->floors()->firstOrFail();
        $floor->update([
            'ui_map_id'    => self::UI_MAP_ID,
            'ingame_min_x' => 0,
            'ingame_max_x' => 300,
            'ingame_min_y' => 0,
            'ingame_max_y' => 300,
        ]);

        return $floor;
    }

    private function createUser(string $roleName): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', $roleName));

        return $user;
    }

    private function deleteUser(User $user): void
    {
        Pennant::for($user)->forget(WalkGraphLayer::class);
        $user->delete();
    }

    private function findAdminRow(): ?Feature
    {
        return Feature::query()
            ->where('name', WalkGraphLayer::class)
            ->where('scope', sprintf('%s|%d', User::class, Feature::ADMIN_USER_ID))
            ->first();
    }

    private function setAdminValue(bool $active): void
    {
        $adminRow        = $this->findAdminRow() ?? new Feature();
        $adminRow->name  = WalkGraphLayer::class;
        $adminRow->scope = sprintf('%s|%d', User::class, Feature::ADMIN_USER_ID);
        $adminRow->value = $active ? 'true' : 'false';
        $adminRow->save();
    }
}
