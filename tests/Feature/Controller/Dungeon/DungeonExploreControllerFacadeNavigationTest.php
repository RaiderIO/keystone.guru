<?php

namespace Tests\Feature\Controller\Dungeon;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonExplore')]
final class DungeonExploreControllerFacadeNavigationTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function viewDungeonFloor_givenFacadeNavigationAndAFloorBehindTheFacade_loadsThatFloorsOwnMapping(): void
    {
        // Arrange
        [$dungeon, $mappingVersion, $facadeFloor, $floor] = $this->getFacadeDungeonFloors();
        $user                                             = User::factory()->create(['map_facade_style' => User::MAP_FACADE_STYLE_FACADE]);

        try {
            $facadeFloor->update(['facade_navigation' => 1]);

            // Act
            $response = $this->viewFloor($user, $dungeon, $mappingVersion, $floor);

            // Assert
            $response->assertOk();
            $response->assertSee('"mapFacadeStyle":"facade"', false);
            $response->assertSee('"mapFacadeStyleForMappingVersion":"split_floors"', false);
        } finally {
            $facadeFloor->update(['facade_navigation' => 0]);
            $user->delete();
        }
    }

    #[Test]
    public function viewDungeonFloor_givenFacadeNavigationAndTheFacadeFloor_loadsTheFacadeMapping(): void
    {
        // Arrange
        [$dungeon, $mappingVersion, $facadeFloor] = $this->getFacadeDungeonFloors();
        $user                                     = User::factory()->create(['map_facade_style' => User::MAP_FACADE_STYLE_FACADE]);

        try {
            $facadeFloor->update(['facade_navigation' => 1]);

            // Act
            $response = $this->viewFloor($user, $dungeon, $mappingVersion, $facadeFloor);

            // Assert
            $response->assertOk();
            $response->assertSee('"mapFacadeStyleForMappingVersion":"facade"', false);
        } finally {
            $facadeFloor->update(['facade_navigation' => 0]);
            $user->delete();
        }
    }

    /**
     * @return TestResponse<Response>
     */
    private function viewFloor(User $user, Dungeon $dungeon, MappingVersion $mappingVersion, Floor $floor): TestResponse
    {
        return $this->actingAs($user)->get(route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $mappingVersion->gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $floor->index,
        ]));
    }

    /**
     * @return array{0: Dungeon, 1: MappingVersion, 2: Floor, 3: Floor}
     */
    private function getFacadeDungeonFloors(): array
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: true, facadeNavigation: false, dungeonActive: true);
        /** @var Floor $facadeFloor */
        $facadeFloor = $dungeon->floors()->where('facade', 1)->firstOrFail();
        /** @var Floor $floor */
        $floor = $dungeon->floors()->where('facade', 0)->where('active', 1)->firstOrFail();

        return [$dungeon, $mappingVersion, $facadeFloor, $floor];
    }
}
