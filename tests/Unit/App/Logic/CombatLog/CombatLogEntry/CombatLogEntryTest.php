<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatLogEntry;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCases\PublicTestCase;

final class CombatLogEntryTest extends PublicTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // CombatLogEntry::$previousDateFormat is static and memoizes across every parse in the
        // process, so a format learned by an earlier test would otherwise leak into this one.
        $previousDateFormatProperty = new ReflectionClass(CombatLogEntry::class)->getProperty('previousDateFormat');
        $previousDateFormatProperty->setAccessible(true);
        $previousDateFormatProperty->setValue(null, null);
    }

    /**
     * Characterization test: with the current DATE_FORMATS entries, exactly one format ever
     * matches a given regex-reachable timestamp tail (the greedy, non-backtracking `v` millisecond
     * specifier combined with Carbon's trailing-data strictness makes overlap impossible - see the
     * DATE_FORMATS doc comment). This asserts that invariant holds, so that DATE_FORMATS can safely
     * stay ordered by intended precedence without ever silently relying on last-match-wins again.
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('CombatLogEntry')]
    #[DataProvider('parseTimestamp_ShouldMatchAtMostOneDateFormat_GivenTimestampTail_DataProvider')]
    public function parseTimestamp_ShouldMatchAtMostOneDateFormat_GivenTimestampTail(string $timestamp): void
    {
        // Act
        $matchedFormats = [];
        foreach (CombatLogEntry::DATE_FORMATS as $key => $dateFormat) {
            try {
                Carbon::createFromFormat($dateFormat, $timestamp);

                $matchedFormats[] = $key;
            } catch (InvalidFormatException) {
                continue;
            }
        }

        // Assert
        Assert::assertLessThanOrEqual(
            1,
            count($matchedFormats),
            sprintf(
                'Timestamp %s matched multiple DATE_FORMATS entries (%s) - array order is now load-bearing for which one wins.',
                $timestamp,
                implode(', ', $matchedFormats),
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function parseTimestamp_ShouldMatchAtMostOneDateFormat_GivenTimestampTail_DataProvider(): array
    {
        $suffixes   = ['', '-1', '-2', '-9', '-10', '-14', '0', '1', '9', '10', '14'];
        $msVariants = ['9', '94', '941'];

        $cases = [];
        foreach ($msVariants as $msVariant) {
            foreach ($suffixes as $suffix) {
                $timestamp = sprintf('5/15/2024 21:20:10.%s%s', $msVariant, $suffix);

                $cases[$timestamp] = [$timestamp];
            }
        }

        return $cases;
    }

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
        int    $expectedMilliseconds,
    ): void {
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

    /**
     * @return array<int, mixed>
     */
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
