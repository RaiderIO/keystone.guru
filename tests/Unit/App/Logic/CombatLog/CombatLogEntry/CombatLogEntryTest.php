<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatLogEntry;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use PHPUnit\Framework\Assert;
use Tests\TestCases\PublicTestCase;

final class CombatLogEntryTest extends PublicTestCase
{

    /**
     * @return void
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('CombatLogEntry')]
    #[DataProvider('parseEvent_ShouldParseTimestamp_GivenRawLogLine_DataProvider')]
    public function parseEvent_ShouldParseTimestamp_GivenRawLogLine(
        string $rawEvent,
        int    $expectedDay,
        int    $expectedMonth,
        int    $expectedHour,
        int    $expectedMinute,
        int    $expectedSecond,
        int    $expectedMilliseconds
    ): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry($rawEvent);

        // Act
        /** @var ChallengeModeStart $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent();

        // Assert
        Assert::assertEquals($expectedDay, $combatLogEntry->getParsedTimestamp()->day);
        Assert::assertEquals($expectedMonth, $combatLogEntry->getParsedTimestamp()->month);
        Assert::assertEquals($expectedHour, $combatLogEntry->getParsedTimestamp()->hour);
        Assert::assertEquals($expectedMinute, $combatLogEntry->getParsedTimestamp()->minute);
        Assert::assertEquals($expectedSecond, $combatLogEntry->getParsedTimestamp()->second);
        Assert::assertEquals($expectedMilliseconds, $combatLogEntry->getParsedTimestamp()->millisecond);
    }

    public static function parseEvent_ShouldParseTimestamp_GivenRawLogLine_DataProvider(): array
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
