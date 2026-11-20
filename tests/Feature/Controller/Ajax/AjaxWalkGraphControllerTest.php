<?php

namespace Tests\Feature\Controller\Ajax;

use App\Features\WalkGraphLayer;
use App\Logic\WalkGraph\WalkGrid;
use App\Models\Feature\Feature;
use App\Models\Floor\Floor;
use App\Models\Laratrust\Role;
use App\Models\User;
use App\Models\WalkGraph\WalkGraph;
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
