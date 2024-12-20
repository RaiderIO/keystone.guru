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
        array  $expectedAffixIds
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
        ];
    }
}
