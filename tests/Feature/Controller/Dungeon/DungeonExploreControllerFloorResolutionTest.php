<?php

namespace Tests\Feature\Controller\Dungeon;

use App\Models\Floor\Floor;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonExplore')]
final class DungeonExploreControllerFloorResolutionTest extends PublicTestCase
{
    use ProvidesDungeon;

    private ?string $originalAdminMapFacadeStyle = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $admin                             = User::findOrFail(1);
        $this->originalAdminMapFacadeStyle = $admin->map_facade_style;
        $admin->update(['map_facade_style' => User::MAP_FACADE_STYLE_SPLIT_FLOORS]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            User::findOrFail(1)->update(['map_facade_style' => $this->originalAdminMapFacadeStyle]);
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function viewDungeon_givenActiveDungeon_redirectsToDefaultFloor(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, dungeonActive: true, requireDefaultFloor: true);
        $gameVersion                = $mappingVersion->gameVersion;
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        // Act
        $response = $this->get(route('dungeon.explore.gameversion.view', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]));

        // Assert
        $response->assertRedirect(route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $defaultFloor->index,
        ]));
    }

    #[Test]
    public function viewDungeonFloor_givenExistingFloorIndex_returnsOk(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, dungeonActive: true, requireDefaultFloor: true);
        $gameVersion                = $mappingVersion->gameVersion;
        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        // Act
        $response = $this->get(route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $floor->index,
        ]));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function viewDungeonFloor_givenNonExistentFloorIndex_redirectsToDefaultFloor(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, dungeonActive: true, requireDefaultFloor: true);
        $gameVersion                = $mappingVersion->gameVersion;
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        // Act
        $response = $this->get(route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => 999999,
        ]));

        // Assert
        $response->assertRedirect(route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $defaultFloor->index,
        ]));
    }

    #[Test]
    public function viewDungeonFloor_givenNonNumericFloorIndex_behavesAsFloorIndexOne(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, dungeonActive: true, requireDefaultFloor: true);
        $gameVersion                = $mappingVersion->gameVersion;

        // Act
        $expectedResponse = $this->get(route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => 1,
        ]));
        $response = $this->get(route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => 'not-a-number',
        ]));

        // Assert
        $response->assertStatus($expectedResponse->getStatusCode());
        if ($expectedResponse->isRedirect()) {
            $response->assertRedirect($expectedResponse->headers->get('Location'));
        }
    }

    #[Test]
    public function embed_givenExistingFloorIndex_returnsOk(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, dungeonActive: true, requireDefaultFloor: true);
        $gameVersion                = $mappingVersion->gameVersion;
        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        // Act
        $response = $this->get(route('dungeon.explore.gameversion.embed.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $floor->index,
        ]));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function embed_givenNonExistentFloorIndex_redirectsToDefaultFloor(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, dungeonActive: true, requireDefaultFloor: true);
        $gameVersion                = $mappingVersion->gameVersion;
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        // Act
        $response = $this->get(route('dungeon.explore.gameversion.embed.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => 999999,
        ]));

        // Assert
        $response->assertRedirect(route('dungeon.explore.gameversion.embed.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $defaultFloor->index,
        ]));
    }
}
