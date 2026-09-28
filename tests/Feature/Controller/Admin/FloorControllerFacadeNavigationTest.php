<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Laratrust\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Admin')]
final class FloorControllerFacadeNavigationTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::findOrFail(1);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');

        $this->be($admin);
    }

    /** @param array<string, mixed> $attributes */
    private function createFloor(Dungeon $dungeon, array $attributes = []): Floor
    {
        return Floor::create(array_merge([
            'dungeon_id'   => $dungeon->id,
            'index'        => 99,
            'ui_map_id'    => 1,
            'name'         => 'Test floor',
            'ingame_min_x' => 0,
            'ingame_min_y' => 0,
            'ingame_max_x' => 1000,
            'ingame_max_y' => 1000,
        ], $attributes));
    }

    /** @return array<string, mixed> */
    private function validPayload(Floor $floor): array
    {
        return [
            'name'         => $floor->name,
            'index'        => $floor->index,
            'ui_map_id'    => $floor->ui_map_id,
            'ingame_min_x' => $floor->ingame_min_x,
            'ingame_min_y' => $floor->ingame_min_y,
            'ingame_max_x' => $floor->ingame_max_x,
            'ingame_max_y' => $floor->ingame_max_y,
        ];
    }

    #[Test]
    public function update_givenFacadeNavigationChecked_persistsIt(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->whereHas('floors')->firstOrFail();
        $floor   = null;

        try {
            $floor = $this->createFloor($dungeon, ['facade' => 1]);

            // Act
            $response = $this->patch(route('admin.floor.update', ['dungeon' => $dungeon, 'floor' => $floor]), array_merge(
                $this->validPayload($floor),
                ['facade' => 1, 'facade_navigation' => 1],
            ));

            // Assert
            $response->assertOk();
            $this->assertSame(1, $floor->fresh()->facade_navigation);
        } finally {
            $floor?->delete();
        }
    }

    #[Test]
    public function update_givenFacadeNavigationUnchecked_clearsIt(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->whereHas('floors')->firstOrFail();
        $floor   = null;

        try {
            $floor = $this->createFloor($dungeon, ['facade' => 1, 'facade_navigation' => 1]);

            // Act - an unchecked checkbox is simply absent from the form post
            $response = $this->patch(route('admin.floor.update', ['dungeon' => $dungeon, 'floor' => $floor]), array_merge(
                $this->validPayload($floor),
                ['facade' => 1],
            ));

            // Assert
            $response->assertOk();
            $this->assertSame(0, $floor->fresh()->facade_navigation);
        } finally {
            $floor?->delete();
        }
    }

    #[Test]
    public function update_givenNonBooleanFacadeNavigation_returnsValidationError(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->whereHas('floors')->firstOrFail();
        $floor   = null;

        try {
            $floor = $this->createFloor($dungeon);

            // Act
            $response = $this->patch(route('admin.floor.update', ['dungeon' => $dungeon, 'floor' => $floor]), array_merge(
                $this->validPayload($floor),
                ['facade_navigation' => 'not-a-bool'],
            ));

            // Assert
            $response->assertSessionHasErrors('facade_navigation');
        } finally {
            $floor?->delete();
        }
    }
}
