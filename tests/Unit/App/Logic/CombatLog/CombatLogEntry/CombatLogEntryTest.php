<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatLogEntry;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use PHPUnit\Framework\Assert;
use Tests\PublicTestCase;
use Tests\TestCase;

class CombatLogEntryTest extends PublicTestCase
{

    /**
     * @test
     * @return void
     * @group CombatLog
     * @group CombatLogEntry
     * @dataProvider parseEvent_ShouldParseTimestamp_GivenRawLogLine_DataProvider
     */
    public function parseEvent_ShouldParseTimestamp_GivenRawLogLine(
        string $rawEvent,
        int    $expectedDay,
        int    $expectedMonth,
        int    $expectedHour,
        int    $expectedMinute,
        int    $expectedSecond,
        int    $expectedMilliseconds
    )
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry($rawEvent);

        // Act
        /** @var ChallengeModeStart $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent();

        // Assert
        Assert::assertEquals($expectedDay, $combatLogEntry->getTimestamp()->day);
        Assert::assertEquals($expectedMonth, $combatLogEntry->getTimestamp()->month);
        Assert::assertEquals($expectedHour, $combatLogEntry->getTimestamp()->hour);
        Assert::assertEquals($expectedMinute, $combatLogEntry->getTimestamp()->minute);
        Assert::assertEquals($expectedSecond, $combatLogEntry->getTimestamp()->second);
        Assert::assertEquals($expectedMilliseconds, $combatLogEntry->getTimestamp()->millisecond);
    }

    public function parseEvent_ShouldParseTimestamp_GivenRawLogLine_DataProvider(): array
    {
        return [
            [
                '5/15 21:20:10.941  CHALLENGE_MODE_START,"The Underrot",1841,251,2,[9]',
                15,
                5,
                21,
                20,
                10,
                941,
            ], [
                '5/15 21:53:59.958  CHALLENGE_MODE_START,"Neltharus",2519,404,4,[9]',
                15,
                5,
                21,
                53,
                59,
                958,
            ],
        ];
    }
}
