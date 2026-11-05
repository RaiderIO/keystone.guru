<?php

namespace Tests\Feature\Mapping;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('Mapping')]
#[Group('Forever')]
final class ForeverDungeonsMappingTest extends TestCase
{
    #[Test]
    #[DataProvider('dungeon_givenForeverDungeon_returnsItWithItsSuggestedLevels_dataProvider')]
    public function dungeon_givenForeverDungeon_returnsItWithItsSuggestedLevels(
        DungeonKey $dungeonKey,
        string     $expectedName,
        int        $expectedMinLevel,
        int        $expectedMaxLevel,
        int        $expectedFloorCount,
        string     $expectedDefaultFloorKey,
    ): void {
        // Arrange
        $key = $dungeonKey->value;

        // Act
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::with('floors')->where('key', $key)->first();

        // Assert
        $this->assertNotNull($dungeon, sprintf('Dungeon %s must be seeded', $key));
        $this->assertSame($expectedName, __($dungeon->name, [], 'en_US'));
        $this->assertSame(Expansion::ALL[Expansion::EXPANSION_CLASSIC], $dungeon->expansion_id);
        $this->assertSame(Expansion::EXPANSION_CLASSIC, $dungeonKey->expansionKey());
        $this->assertFalse((bool)$dungeon->raid);
        $this->assertSame($expectedMinLevel, $dungeon->min_suggested_level);
        $this->assertSame($expectedMaxLevel, $dungeon->max_suggested_level);
        $this->assertCount($expectedFloorCount, $dungeon->floors);
        $defaultFloors = $dungeon->floors->filter(static fn($floor) => (bool)$floor->default);
        $this->assertCount(1, $defaultFloors);
        $this->assertSame(sprintf('dungeons.classic.%s.floors.%s', $key, $expectedDefaultFloorKey), $defaultFloors->first()->name);
    }

    /**
     * @return array<string, array{DungeonKey, string, int, int, int, string}>
     */
    public static function dungeon_givenForeverDungeon_returnsItWithItsSuggestedLevels_dataProvider(): array
    {
        return [
            'The Hall of Thanes'        => [DungeonKey::THE_HALL_OF_THANES, 'The Hall of Thanes', 13, 18, 1, 'the_hall_of_thanes'],
            'Ruins of Lordaeron'        => [DungeonKey::RUINS_OF_LORDAERON, 'Ruins of Lordaeron', 15, 20, 1, 'ruins_of_lordaeron'],
            'Excavation Site: Wetlands' => [DungeonKey::EXCAVATION_SITE_WETLANDS, 'Excavation Site: Wetlands', 24, 29, 1, 'excavation_site_wetlands'],
            'City of Dalaran'           => [DungeonKey::CITY_OF_DALARAN, 'City of Dalaran', 28, 33, 2, 'the_underbelly'],
            'The Drowned City'          => [DungeonKey::THE_DROWNED_CITY, 'The Drowned City', 35, 40, 2, 'the_drowned_city'],
            "Krol'dok Stronghold"       => [DungeonKey::KROLDOK_STRONGHOLD, "Krol'dok Stronghold", 40, 45, 1, 'kroldok_stronghold'],
            'Alcaz Prison'              => [DungeonKey::ALCAZ_PRISON, 'Alcaz Prison', 48, 53, 1, 'alcaz_prison'],
            'Blackmaw Hold'             => [DungeonKey::BLACKMAW_HOLD, 'Blackmaw Hold', 55, 60, 1, 'blackmaw_hold'],
            "Shaper's Terrace"          => [DungeonKey::SHAPERS_TERRACE, "Shaper's Terrace", 58, 60, 1, 'shapers_terrace'],
        ];
    }

    #[Test]
    #[DataProvider('mappingVersion_givenForeverDungeon_returnsOneEmptyForeverMappingVersion_dataProvider')]
    public function mappingVersion_givenForeverDungeon_returnsOneEmptyForeverMappingVersion(DungeonKey $dungeonKey): void
    {
        // Arrange
        $foreverGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_FOREVER)->firstOrFail();
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::query()->where('key', $dungeonKey->value)->firstOrFail();

        // Act
        $mappingVersions = $dungeon->mappingVersions()->get();

        // Assert
        $this->assertCount(1, $mappingVersions);
        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $mappingVersions->first();
        $this->assertSame($foreverGameVersion->id, $mappingVersion->game_version_id);
        $this->assertSame(1, $mappingVersion->version);
        $this->assertSame($mappingVersion->id, $dungeon->getCurrentMappingVersionForGameVersion($foreverGameVersion)?->id);
        $this->assertSame(0, $mappingVersion->enemies()->count());
        $this->assertSame(0, $mappingVersion->mapIcons()->count());
        $this->assertSame(0, $mappingVersion->dungeonStarts()->count());
    }

    /**
     * @return array<string, array{DungeonKey}>
     */
    public static function mappingVersion_givenForeverDungeon_returnsOneEmptyForeverMappingVersion_dataProvider(): array
    {
        return [
            'Ruins of Lordaeron'        => [DungeonKey::RUINS_OF_LORDAERON],
            'Excavation Site: Wetlands' => [DungeonKey::EXCAVATION_SITE_WETLANDS],
            'The Drowned City'          => [DungeonKey::THE_DROWNED_CITY],
            "Krol'dok Stronghold"       => [DungeonKey::KROLDOK_STRONGHOLD],
            'Alcaz Prison'              => [DungeonKey::ALCAZ_PRISON],
            'Blackmaw Hold'             => [DungeonKey::BLACKMAW_HOLD],
            "Shaper's Terrace"          => [DungeonKey::SHAPERS_TERRACE],
        ];
    }

    #[Test]
    #[DataProvider('mappingVersion_givenMappedForeverDungeon_returnsOneForeverMappingVersionWithDungeonStart_dataProvider')]
    public function mappingVersion_givenMappedForeverDungeon_returnsOneForeverMappingVersionWithDungeonStart(DungeonKey $dungeonKey): void
    {
        // Arrange
        $foreverGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_FOREVER)->firstOrFail();
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::query()->where('key', $dungeonKey->value)->firstOrFail();

        // Act
        $mappingVersions = $dungeon->mappingVersions()->get();

        // Assert
        $this->assertCount(1, $mappingVersions);
        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $mappingVersions->first();
        $this->assertSame($foreverGameVersion->id, $mappingVersion->game_version_id);
        $this->assertSame(1, $mappingVersion->version);
        $this->assertSame($mappingVersion->id, $dungeon->getCurrentMappingVersionForGameVersion($foreverGameVersion)?->id);
        $this->assertSame(1, $mappingVersion->dungeonStarts()->count());
    }

    /**
     * @return array<string, array{DungeonKey}>
     */
    public static function mappingVersion_givenMappedForeverDungeon_returnsOneForeverMappingVersionWithDungeonStart_dataProvider(): array
    {
        return [
            'The Hall of Thanes' => [DungeonKey::THE_HALL_OF_THANES],
            'City of Dalaran'    => [DungeonKey::CITY_OF_DALARAN],
        ];
    }
}
