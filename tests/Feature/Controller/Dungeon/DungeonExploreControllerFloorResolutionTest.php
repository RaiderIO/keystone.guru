<?php

namespace Tests\Feature\Controller\Dungeon;

use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonExplore')]
final class DungeonExploreControllerFloorResolutionTest extends PublicTestCase
{
    use CreatesDungeon;
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
    public function embed_givenExistingFloorIndex_loadsTheDungeonsMappingVersionsOnce(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, dungeonActive: true, requireDefaultFloor: true);
        $gameVersion                = $mappingVersion->gameVersion;
        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();
        $url   = route('dungeon.explore.gameversion.embed.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $floor->index,
        ]);

        $mappingVersionQueries = 0;
        DB::listen(static function (QueryExecuted $query) use (&$mappingVersionQueries): void {
            if (str_contains($query->sql, 'from `mapping_versions` where `mapping_versions`.`dungeon_id`')) {
                $mappingVersionQueries++;
            }
        });

        // Act
        $response = $this->get($url);

        // Assert
        $response->assertOk();
        $this->assertSame(1, $mappingVersionQueries);
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

    #[Test]
    public function viewDungeonFloor_givenFacadeNavigationAndNonFacadeFloorIndex_returnsOk(): void
    {
        // Arrange
        $admin = User::findOrFail(1);
        $admin->update(['map_facade_style' => User::MAP_FACADE_STYLE_FACADE]);
        $this->be($admin);
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: true, dungeonActive: true, requireDefaultFloor: true);
        $gameVersion                = $mappingVersion->gameVersion;
        /** @var Floor $facadeFloor */
        $facadeFloor = $dungeon->floors()->where('facade', 1)->firstOrFail();
        /** @var Floor $floor */
        $floor = $dungeon->floors()->where('facade', 0)->where('active', 1)->firstOrFail();

        try {
            $facadeFloor->update(['facade_navigation' => 1]);

            // Act
            $response = $this->get(route('dungeon.explore.gameversion.view.floor', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
                'floorIndex'  => $floor->index,
            ]));

            // Assert
            $response->assertOk();
        } finally {
            $facadeFloor->update(['facade_navigation' => 0]);
        }
    }

    #[Test]
    public function viewDungeonFloor_givenFacadeStyleWithoutFacadeNavigation_redirectsToFacadeFloor(): void
    {
        // Arrange
        $admin = User::findOrFail(1);
        $admin->update(['map_facade_style' => User::MAP_FACADE_STYLE_FACADE]);
        $this->be($admin);
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: true, dungeonActive: true, requireDefaultFloor: true);
        $gameVersion                = $mappingVersion->gameVersion;
        /** @var Floor $facadeFloor */
        $facadeFloor = $dungeon->floors()->where('facade', 1)->firstOrFail();
        /** @var Floor $floor */
        $floor = $dungeon->floors()->where('facade', 0)->where('active', 1)->firstOrFail();

        // Act
        $response = $this->get(route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $floor->index,
        ]));

        // Assert
        $response->assertRedirect(route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $facadeFloor->index,
        ]));
    }

    #[Test]
    public function viewDungeon_givenInactiveDungeon_redirectsToSelect(): void
    {
        // Arrange - inactive, but with a current mapping version and a default floor, so activity is the only
        // reason it can be turned away
        $dungeon     = $this->createDungeon(['active' => false]);
        $gameVersion = $dungeon->getCurrentMappingVersion()->gameVersion;

        // Act
        $response = $this->get(route('dungeon.explore.gameversion.view', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]));

        // Assert
        $response->assertRedirect(route('dungeon.explore.gameversion.select', ['gameVersion' => $gameVersion]));
    }

    #[Test]
    public function viewDungeonFloor_givenInactiveDungeon_redirectsToSelect(): void
    {
        // Arrange
        $dungeon     = $this->createDungeon(['active' => false]);
        $gameVersion = $dungeon->getCurrentMappingVersion()->gameVersion;

        // Act
        $response = $this->get(route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => 1,
        ]));

        // Assert
        $response->assertRedirect(route('dungeon.explore.gameversion.select', ['gameVersion' => $gameVersion]));
    }

    #[Test]
    public function viewDungeonFloor_givenGameVersionWithoutMappingVersion_redirectsToSelect(): void
    {
        // Arrange - active and mapped, but only for the default game version
        $dungeon          = $this->createDungeon(['active' => true]);
        $otherGameVersion = GameVersion::query()
            ->where('id', '!=', $dungeon->getCurrentMappingVersion()->game_version_id)
            ->firstOrFail();

        // Act
        $response = $this->get(route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $otherGameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => 1,
        ]));

        // Assert
        $response->assertRedirect(route('dungeon.explore.gameversion.select', ['gameVersion' => $otherGameVersion]));
    }

    #[Test]
    public function embed_givenInactiveDungeon_redirectsToSelect(): void
    {
        // Arrange
        $dungeon     = $this->createDungeon(['active' => false]);
        $gameVersion = $dungeon->getCurrentMappingVersion()->gameVersion;

        // Act
        $response = $this->get(route('dungeon.explore.gameversion.embed.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => 1,
        ]));

        // Assert
        $response->assertRedirect(route('dungeon.explore.gameversion.select', ['gameVersion' => $gameVersion]));
    }
}
