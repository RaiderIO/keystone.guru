<?php

namespace Tests\Feature\App\Service\Dungeon\DungeonService;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Service\Dungeon\DungeonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonService')]
#[Group('GetExclusiveDungeonIds')]
final class GetExclusiveDungeonIdsTest extends PublicTestCase
{
    #[Test]
    public function getExclusiveDungeonIds_givenForever_returnsOnlyTheDungeonsWithoutAClassicEraMappingVersion(): void
    {
        // Arrange
        $service            = app(DungeonServiceInterface::class);
        $forever            = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_FOREVER);
        $dungeons           = $service->getDungeonsForGameVersion($forever);
        $foreverOnlyDungeon = $dungeons->firstWhere('key', 'the_hall_of_thanes');
        $inheritedDungeon   = $dungeons->first(static fn(Dungeon $dungeon) => $dungeon->mappingVersions()
            ->where('game_version_id', $forever->parent_game_version_id)
            ->exists());

        $this->assertNotNull($foreverOnlyDungeon, 'Need the seeded Forever dungeon The Hall of Thanes');
        $this->assertNotNull($inheritedDungeon, 'Need a seeded Classic Era dungeon that Forever inherits');

        // Act
        $result = $service->getExclusiveDungeonIds($forever, $dungeons);

        // Assert
        $this->assertContains($foreverOnlyDungeon->id, $result);
        $this->assertNotContains($inheritedDungeon->id, $result);
    }

    #[Test]
    public function getExclusiveDungeonIds_givenAGameVersionWithoutParent_returnsEmpty(): void
    {
        // Arrange
        $service    = app(DungeonServiceInterface::class);
        $classicEra = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $dungeons   = $service->getDungeonsForGameVersion($classicEra);

        $this->assertNull($classicEra->parent_game_version_id);
        $this->assertTrue($dungeons->isNotEmpty(), 'Need seeded Classic Era dungeons');

        // Act
        $result = $service->getExclusiveDungeonIds($classicEra, $dungeons);

        // Assert
        $this->assertTrue($result->isEmpty());
    }
}
