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
        $this->assertCount(1, $dungeon->floors);
        $this->assertTrue((bool)$dungeon->floors->first()->default);
        $this->assertSame(sprintf('dungeons.classic.%s.floors.%s', $key, $key), $dungeon->floors->first()->name);
    }

    #[Test]
    #[DataProvider('dungeon_givenForeverDungeon_returnsItWithItsSuggestedLevels_dataProvider')]
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
     * @return array<string, array{DungeonKey, string, int, int}>
     */
    public static function dungeon_givenForeverDungeon_returnsItWithItsSuggestedLevels_dataProvider(): array
    {
        return [
            'The Hall of Thanes'        => [DungeonKey::THE_HALL_OF_THANES, 'The Hall of Thanes', 13, 18],
            'Ruins of Lordaeron'        => [DungeonKey::RUINS_OF_LORDAERON, 'Ruins of Lordaeron', 15, 20],
            'Excavation Site: Wetlands' => [DungeonKey::EXCAVATION_SITE_WETLANDS, 'Excavation Site: Wetlands', 24, 29],
            'City of Dalaran'           => [DungeonKey::CITY_OF_DALARAN, 'City of Dalaran', 28, 33],
            'The Drowned City'          => [DungeonKey::THE_DROWNED_CITY, 'The Drowned City', 35, 40],
            "Krol'dok Stronghold"       => [DungeonKey::KROLDOK_STRONGHOLD, "Krol'dok Stronghold", 40, 45],
            'Alcaz Prison'              => [DungeonKey::ALCAZ_PRISON, 'Alcaz Prison', 48, 53],
            'Blackmaw Hold'             => [DungeonKey::BLACKMAW_HOLD, 'Blackmaw Hold', 55, 60],
            "Shaper's Terrace"          => [DungeonKey::SHAPERS_TERRACE, "Shaper's Terrace", 58, 60],
        ];
    }
}
