<?php

namespace Tests\Unit\App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class ChallengeModeStartTest extends PublicTestCase
{
    /**
     * @param array<int, int> $expectedAffixIds
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('ChallengeModeStart')]
    #[DataProvider('parseEvent_ShouldReturnChallengeModeStartEvent_GivenChallengeModeStartEvent_DataProvider')]
    public function parseEvent_ShouldReturnChallengeModeStartEvent_GivenChallengeModeStartEvent(
        string $challengeModeStartEvent,
        string $expectedZoneName,
        int    $expectedInstanceId,
        int    $expectedChallengeModeId,
        int    $expectedKeystoneLevel,
        array  $expectedAffixIds,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($challengeModeStartEvent);

        // Act
        /** @var ChallengeModeStart $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_10_1_0);

        // Assert
        Assert::assertInstanceOf(ChallengeModeStart::class, $combatLogEntry->getParsedEvent());
        Assert::assertEquals($expectedZoneName, $parseEventResult->getZoneName());
        Assert::assertEquals($expectedInstanceId, $parseEventResult->getInstanceID());
        Assert::assertEquals($expectedChallengeModeId, $parseEventResult->getChallengeModeID());
        Assert::assertEquals($expectedKeystoneLevel, $parseEventResult->getKeystoneLevel());
        Assert::assertEquals($expectedAffixIds, $parseEventResult->getAffixIDs());
    }

    /**
     * @return array<int, mixed>
     */
    public static function parseEvent_ShouldReturnChallengeModeStartEvent_GivenChallengeModeStartEvent_DataProvider(): array
    {
        return [
            [
                '5/15 21:20:10.941  CHALLENGE_MODE_START,"The Underrot",1841,251,2,[9]',
                'The Underrot',
                1841,
                251,
                2,
                [9],
            ],
            [
                '5/15 21:53:59.958  CHALLENGE_MODE_START,"Neltharus",2519,404,4,[9]',
                'Neltharus',
                2519,
                404,
                4,
                [9],
            ],
            // A live key carries three affixes, all inside the one bracketed parameter the string parser hands
            // over verbatim - only the first survived until the ids were split out of it (#4244)
            [
                '8/21/2026 20:47:57.8652  CHALLENGE_MODE_START,"Altar of Fangs",2993,588,10,[162,10,9]',
                'Altar of Fangs',
                2993,
                588,
                10,
                [162, 10, 9],
            ],
            // A key below the level the seasonal affix unlocks at has two
            [
                '8/18/2026 20:08:29.5592  CHALLENGE_MODE_START,"Voidscar Arena",2923,585,7,[10,9]',
                'Voidscar Arena',
                2923,
                585,
                7,
                [10, 9],
            ],
        ];
    }
}
