<?php

namespace Tests\Feature\Mapping;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('Mapping')]
#[Group('Floor')]
final class ContinentFloorDisplayOrderTest extends TestCase
{
    #[Test]
    #[DataProvider('continent_dataProvider')]
    public function displayOrdered_givenContinent_listsTheFacadeFirstThenTheZonesNorthToSouth(DungeonKey $dungeonKey): void
    {
        // Arrange
        $dungeon        = $this->getDungeon($dungeonKey);
        $mappingVersion = $this->getMappingVersion($dungeon);
        /** @var array<int, float> $unionLatByFloorId */
        $unionLatByFloorId = FloorUnion::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->pluck('lat', 'target_floor_id')
            ->all();

        // Act
        $floors = $dungeon->floors()->displayOrdered()->get();

        // Assert
        /** @var Floor $facadeFloor */
        $facadeFloor = $floors->first();
        $this->assertTrue((bool)$facadeFloor->facade, 'The continent overview comes first.');
        $zoneLats = $floors->slice(1)->map(static fn(Floor $floor) => $unionLatByFloorId[$floor->id] ?? null)->values()->all();
        $this->assertNotContains(null, $zoneLats, 'Every zone has a floor union on the facade.');
        $this->assertCount($floors->count() - 1, $zoneLats);
        $sortedNorthToSouth = $zoneLats;
        rsort($sortedNorthToSouth);
        $this->assertSame($sortedNorthToSouth, $zoneLats);
        $this->assertSame($floors->count(), $floors->pluck('display_order')->unique()->count());
    }

    #[Test]
    #[DataProvider('continent_dataProvider')]
    public function view_givenContinentExplorePage_listsTheFloorSwitchInDisplayOrder(DungeonKey $dungeonKey): void
    {
        // Arrange
        $dungeon     = $this->getDungeon($dungeonKey);
        $facadeFloor = $dungeon->floors->firstWhere('facade', true);
        $expectedIds = $dungeon->floors()->displayOrdered()->active()->pluck('id')->all();

        // Act
        $response = $this->get(route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => GameVersion::GAME_VERSION_FOREVER,
            'dungeon'     => $dungeon,
            'floorIndex'  => $facadeFloor->index,
        ]));

        // Assert
        $response->assertOk();
        $this->assertSame($expectedIds, $this->getFloorSwitchFloorIds($response->getContent()));
    }

    #[Test]
    #[DataProvider('continent_dataProvider')]
    public function view_givenContinentMappingEditor_listsTheFloorSwitchInDisplayOrder(DungeonKey $dungeonKey): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        $dungeon        = $this->getDungeon($dungeonKey);
        $mappingVersion = $this->getMappingVersion($dungeon);
        $expectedIds    = $dungeon->floors()->displayOrdered()->pluck('id')->all();

        // Act
        $response = $this->get(route('admin.floor.edit.mapping', [
            'dungeon'         => $dungeon,
            'floor'           => $dungeon->floors->firstWhere('default', true),
            'mapping_version' => $mappingVersion->id,
        ]));

        // Assert
        $response->assertOk();
        $this->assertSame($expectedIds, $this->getFloorSwitchFloorIds($response->getContent()));
    }

    /**
     * @return array<string, array{DungeonKey}>
     */
    public static function continent_dataProvider(): array
    {
        return [
            'Kalimdor'         => [DungeonKey::KALIMDOR],
            'Eastern Kingdoms' => [DungeonKey::EASTERN_KINGDOMS],
        ];
    }

    /**
     * @return list<int>
     */
    private function getFloorSwitchFloorIds(string $html): array
    {
        $this->assertMatchesRegularExpression('/id="map_floor_selection_dropdown"(.*?)<\/div>/s', $html);
        preg_match('/id="map_floor_selection_dropdown"(.*?)<\/div>/s', $html, $dropdown);
        preg_match_all('/data-value="(\d+)"/', $dropdown[1], $matches);

        return array_map('intval', $matches[1]);
    }

    private function getDungeon(DungeonKey $dungeonKey): Dungeon
    {
        return Dungeon::with('floors')->where('key', $dungeonKey->value)->firstOrFail();
    }

    private function getMappingVersion(Dungeon $dungeon): MappingVersion
    {
        $gameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_FOREVER)->firstOrFail();

        return $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);
    }
}
