<?php

namespace Tests\Unit\App\Logic\CombatLog\ChallengeModeStart;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

class ChallengeModeStartTest extends TestCase
{

    /**
     * @test
     * @return void
     * @group CombatLog
     * @group ChallengeModeStart
     * @dataProvider parseEvent_ShouldReturnChallengeModeStartEvent_GivenChallengeModeStartEvent_DataProvider
     */
    public function parseEvent_ShouldReturnChallengeModeStartEvent_GivenChallengeModeStartEvent(
        string $challengeModeStartEvent,
        string $expectedZoneName,
        int    $expectedInstanceId,
        int    $expectedChallengeModeId,
        int    $expectedKeystoneLevel,
        string $expectedAffixIds
    )
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry($challengeModeStartEvent);

        // Act
        /** @var ChallengeModeStart $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent();

        // Assert
        Assert::assertInstanceOf(ChallengeModeStart::class, $combatLogEntry->getParsedEvent());
        Assert::assertEquals($expectedZoneName, $parseEventResult->getZoneName());
        Assert::assertEquals($expectedInstanceId, $parseEventResult->getInstanceID());
        Assert::assertEquals($expectedChallengeModeId, $parseEventResult->getChallengeModeID());
        Assert::assertEquals($expectedKeystoneLevel, $parseEventResult->getKeystoneLevel());
        Assert::assertEquals($expectedAffixIds, $parseEventResult->getAffixIDs());
    }

    public function parseEvent_ShouldReturnChallengeModeStartEvent_GivenChallengeModeStartEvent_DataProvider(): array
    {
        return [
            [
                '5/15 21:20:10.941  CHALLENGE_MODE_START,"The Underrot",1841,251,2,[9]',
                'The Underrot',
                1841,
                251,
                2,
                '[9]',
            ], [
                '5/15 21:53:59.958  CHALLENGE_MODE_START,"Neltharus",2519,404,4,[9]',
                'Neltharus',
                2519,
                404,
                4,
                '[9]',
            ],
        ];
    }
}
