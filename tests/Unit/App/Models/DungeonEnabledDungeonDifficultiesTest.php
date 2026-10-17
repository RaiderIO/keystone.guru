<?php

namespace Tests\Unit\App\Models;

use App\Models\Dungeon;
use App\Models\DungeonDifficulty;
use App\Models\Speedrun\DungeonSpeedrunDifficulty;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
#[Group('DungeonDifficulty')]
final class DungeonEnabledDungeonDifficultiesTest extends PublicTestCase
{
    /**
     * @param list<int>               $enabledDifficulties
     * @param list<DungeonDifficulty> $expected
     */
    #[Test]
    #[DataProvider('enabledDifficultiesProvider')]
    public function getEnabledDungeonDifficulties_givenEnabledDifficulties_returnsThemInCaseOrder(array $enabledDifficulties, array $expected): void
    {
        // Arrange
        $dungeon = new Dungeon();
        $dungeon->setRelation('dungeonSpeedrunDifficulties', collect($enabledDifficulties)->map(
            static fn(int $difficulty) => new DungeonSpeedrunDifficulty(['difficulty' => $difficulty]),
        ));

        // Act
        $result = $dungeon->getEnabledDungeonDifficulties();

        // Assert
        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{list<int>, list<DungeonDifficulty>}>
     */
    public static function enabledDifficultiesProvider(): array
    {
        return [
            'none'               => [[], []],
            'only 25-man'        => [[2], [DungeonDifficulty::TWENTY_FIVE_MAN]],
            'all four, shuffled' => [
                [4, 2, 1, 3],
                [DungeonDifficulty::TEN_MAN, DungeonDifficulty::TWENTY_FIVE_MAN, DungeonDifficulty::TWENTY_MAN, DungeonDifficulty::FORTY_MAN],
            ],
            'unknown id is ignored' => [[9, 4], [DungeonDifficulty::FORTY_MAN]],
        ];
    }
}
