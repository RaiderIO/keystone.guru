<?php

namespace Tests\Feature\Controller\Dungeon;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonHeatmap')]
final class DungeonHeatmapControllerContinentTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('continentProvider')]
    public function viewDungeonFloor_givenContinentFacadeFloor_rendersTheHeatmap(DungeonKey $dungeonKey): void
    {
        // Arrange
        [$dungeon, $gameVersion] = $this->getContinent($dungeonKey);
        /** @var Floor $facadeFloor */
        $facadeFloor = $dungeon->floors()->where('facade', true)->firstOrFail();

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $facadeFloor->index,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('dungeon.heatmap.gameversion.view');
    }

    #[Test]
    #[DataProvider('continentProvider')]
    public function viewDungeonFloor_givenContinentZoneFloorThroughFacadeNavigation_rendersTheHeatmap(DungeonKey $dungeonKey): void
    {
        // Arrange
        [$dungeon, $gameVersion] = $this->getContinent($dungeonKey);
        /** @var Floor $zoneFloor */
        $zoneFloor = $dungeon->floors()->where('facade', false)->where('default', true)->firstOrFail();
        $user      = User::factory()->create(['map_facade_style' => User::MAP_FACADE_STYLE_FACADE]);

        try {
            $this->be($user);

            // Act
            $response = $this->get(route('dungeon.heatmap.gameversion.view.floor', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
                'floorIndex'  => $zoneFloor->index,
            ]));
        } finally {
            $user->delete();
        }

        // Assert
        $response->assertOk();
        $response->assertViewHas('floor', fn(Floor $floor) => $floor->id === $zoneFloor->id);
    }

    #[Test]
    #[DataProvider('continentProvider')]
    public function viewDungeonFloor_givenContinentZoneFloorOnSplitFloors_rendersTheHeatmap(DungeonKey $dungeonKey): void
    {
        // Arrange
        [$dungeon, $gameVersion] = $this->getContinent($dungeonKey);
        /** @var Floor $zoneFloor */
        $zoneFloor = $dungeon->floors()->where('facade', false)->where('default', true)->firstOrFail();
        $user      = User::factory()->create(['map_facade_style' => User::MAP_FACADE_STYLE_SPLIT_FLOORS]);

        try {
            $this->be($user);

            // Act
            $response = $this->get(route('dungeon.heatmap.gameversion.view.floor', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
                'floorIndex'  => $zoneFloor->index,
            ]));
        } finally {
            $user->delete();
        }

        // Assert
        $response->assertOk();
        $response->assertViewHas('floor', fn(Floor $floor) => $floor->id === $zoneFloor->id);
    }

    #[Test]
    #[DataProvider('continentProvider')]
    public function embed_givenContinent_rendersTheHeatmapEmbed(DungeonKey $dungeonKey): void
    {
        // Arrange
        [$dungeon, $gameVersion] = $this->getContinent($dungeonKey);

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion.embed', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('dungeon.heatmap.gameversion.embed');
    }

    #[Test]
    #[DataProvider('continentProvider')]
    public function select_givenContinentGameVersion_linksToTheContinentHeatmap(DungeonKey $dungeonKey): void
    {
        // Arrange
        [$dungeon, $gameVersion] = $this->getContinent($dungeonKey);

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion.select', ['gameVersion' => $gameVersion]));

        // Assert
        $response->assertOk();
        $response->assertSee(route('dungeon.heatmap.gameversion.view', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]), false);
    }

    #[Test]
    #[DataProvider('continentProvider')]
    public function viewDungeonFloor_givenContinentWithoutMapId_redirectsToTheHeatmapSelection(DungeonKey $dungeonKey): void
    {
        // Arrange
        [$dungeon, $gameVersion] = $this->getContinent($dungeonKey);
        /** @var Floor $facadeFloor */
        $facadeFloor = $dungeon->floors()->where('facade', true)->firstOrFail();
        $mapId       = $dungeon->map_id;

        try {
            Dungeon::query()->whereKey($dungeon->id)->update(['map_id' => null]);

            // Act
            $response = $this->get(route('dungeon.heatmap.gameversion.view.floor', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
                'floorIndex'  => $facadeFloor->index,
            ]));
        } finally {
            Dungeon::query()->whereKey($dungeon->id)->update(['map_id' => $mapId]);
        }

        // Assert
        $response->assertRedirect(route('dungeon.heatmap.gameversion.select', ['gameVersion' => $gameVersion]));
    }

    #[Test]
    #[DataProvider('continentProvider')]
    public function embed_givenContinentWithoutMapId_rendersTheUnsupportedEmbed(DungeonKey $dungeonKey): void
    {
        // Arrange
        [$dungeon, $gameVersion] = $this->getContinent($dungeonKey);
        $mapId                   = $dungeon->map_id;

        try {
            Dungeon::query()->whereKey($dungeon->id)->update(['map_id' => null]);

            // Act
            $response = $this->get(route('dungeon.heatmap.gameversion.embed', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
            ]));
        } finally {
            Dungeon::query()->whereKey($dungeon->id)->update(['map_id' => $mapId]);
        }

        // Assert
        $response->assertOk();
        $response->assertViewIs('dungeon.heatmap.gameversion.embedunsupported');
    }

    /**
     * @return array<string, array{0: DungeonKey}>
     */
    public static function continentProvider(): array
    {
        return [
            'kalimdor'         => [DungeonKey::KALIMDOR],
            'eastern kingdoms' => [DungeonKey::EASTERN_KINGDOMS],
        ];
    }

    /**
     * @return array{0: Dungeon, 1: GameVersion}
     */
    private function getContinent(DungeonKey $dungeonKey): array
    {
        $dungeon = Dungeon::where('key', $dungeonKey->value)->firstOrFail();

        return [$dungeon, $dungeon->getCurrentMappingVersion()->gameVersion];
    }
}
