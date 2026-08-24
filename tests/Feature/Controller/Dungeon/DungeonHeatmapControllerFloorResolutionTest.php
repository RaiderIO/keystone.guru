<?php

namespace Tests\Feature\Controller\Dungeon;

use App\Features\Heatmap;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonHeatmap')]
final class DungeonHeatmapControllerFloorResolutionTest extends PublicTestCase
{
    use ProvidesDungeon;

    private ?string $originalAdminMapFacadeStyle = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        Feature::define(Heatmap::class, true);

        $admin                             = User::findOrFail(1);
        $this->originalAdminMapFacadeStyle = $admin->map_facade_style;
        $admin->update(['map_facade_style' => User::MAP_FACADE_STYLE_SPLIT_FLOORS]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            User::findOrFail(1)->update(['map_facade_style' => $this->originalAdminMapFacadeStyle]);
            Feature::purge(Heatmap::class);
        } finally {
            parent::tearDown();
        }
    }

    /**
     * @return array{0: Dungeon, 1: \App\Models\Mapping\MappingVersion}
     */
    private function findHeatmapDungeon(): array
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(
            facadeEnabled:       false,
            dungeonActive:       true,
            requireDefaultFloor: true,
            constraint:          static fn(Builder $query) => $query->where('heatmap_enabled', 1),
        );

        return [$dungeon, $mappingVersion];
    }

    #[Test]
    public function viewDungeonFloor_givenExistingFloorIndex_returnsOk(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findHeatmapDungeon();
        $gameVersion                = $mappingVersion->gameVersion;
        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion.view.floor', [
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
        [$dungeon, $mappingVersion] = $this->findHeatmapDungeon();
        $gameVersion                = $mappingVersion->gameVersion;
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => 999999,
        ]));

        // Assert
        $response->assertRedirect(route('dungeon.heatmap.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $defaultFloor->index,
        ]));
    }

    #[Test]
    public function embed_givenExistingFloorIndex_returnsOk(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findHeatmapDungeon();
        $gameVersion                = $mappingVersion->gameVersion;
        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion.embed.floor', [
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
        [$dungeon, $mappingVersion] = $this->findHeatmapDungeon();
        $gameVersion                = $mappingVersion->gameVersion;
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion.embed.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => 999999,
        ]));

        // Assert
        $response->assertRedirect(route('dungeon.heatmap.gameversion.embed.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $defaultFloor->index,
        ]));
    }

    #[Test]
    public function viewDungeon_givenActiveDungeon_redirectsToDefaultFloor(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findHeatmapDungeon();
        $gameVersion                = $mappingVersion->gameVersion;
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion.view', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]));

        // Assert
        $response->assertRedirect(route('dungeon.heatmap.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $defaultFloor->index,
        ]));
    }
}
