<?php

namespace Tests\Unit\App\Models;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\RaidKey;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards the disjointness invariant the {@see DungeonKey}/{@see RaidKey} split depends on -
 * {@see Dungeon::findExpansionByKey()} silently falls back to whichever enum is tried first
 * (DungeonKey) when a value collides, and {@see Dungeon::allKeys()} would emit a duplicate into the
 * `Rule::in` list built from it.
 */
#[Group('Models')]
final class DungeonKeyTest extends PublicTestCase
{
    #[Test]
    public function dungeonKeyAndRaidKey_givenBothEnums_haveDisjointValueSets(): void
    {
        // Arrange
        $dungeonKeyValues = array_column(DungeonKey::cases(), 'value');
        $raidKeyValues    = array_column(RaidKey::cases(), 'value');

        // Act
        $overlap = array_intersect($dungeonKeyValues, $raidKeyValues);

        // Assert
        $this->assertSame(
            [],
            array_values($overlap),
            'DungeonKey and RaidKey must never share a value - a colliding key would make Dungeon::findExpansionByKey() '
            . 'silently resolve to the wrong expansion.',
        );
    }

    #[Test]
    public function allKeys_givenEveryDungeonAndRaidKey_containsNoDuplicates(): void
    {
        // Arrange
        $allKeys = Dungeon::allKeys();

        // Act
        $duplicateKeys = array_unique(array_diff_assoc($allKeys, array_unique($allKeys)));

        // Assert
        $this->assertSame(
            [],
            array_values($duplicateKeys),
            'Dungeon::allKeys() must contain no duplicates - it feeds Rule::in() lists used for request validation.',
        );
    }

    #[Test]
    public function raidFlag_givenSeededRaidKeys_isSetForEveryRaid(): void
    {
        // Arrange
        $raidKeyValues = array_column(RaidKey::cases(), 'value');

        // Act
        $raidsNotFlaggedAsRaid = $this->seededDungeonKeysWhere(
            static fn(array $dungeon) => in_array($dungeon['key'], $raidKeyValues, true) && !$dungeon['raid'],
        );

        // Assert
        $this->assertSame([], $raidsNotFlaggedAsRaid, 'Every RaidKey must be seeded with raid = 1.');
    }

    #[Test]
    public function raidFlag_givenSeededDungeonKeys_isNotSetForAnyDungeon(): void
    {
        // Arrange
        $dungeonKeyValues = array_column(DungeonKey::cases(), 'value');

        // Act
        $dungeonsFlaggedAsRaid = $this->seededDungeonKeysWhere(
            static fn(array $dungeon) => in_array($dungeon['key'], $dungeonKeyValues, true) && $dungeon['raid'],
        );

        // Assert
        $this->assertSame([], $dungeonsFlaggedAsRaid, 'Every DungeonKey must be seeded with raid = 0.');
    }

    /**
     * @param callable(array{key: string, raid: int}): bool $filterFn
     *
     * @return list<string>
     */
    private function seededDungeonKeysWhere(callable $filterFn): array
    {
        /** @var list<array{key: string, raid: int}> $seededDungeons */
        $seededDungeons = json_decode(
            file_get_contents(database_path('seeders/dungeondata/dungeons.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        return array_column(array_filter($seededDungeons, $filterFn), 'key');
    }
}
