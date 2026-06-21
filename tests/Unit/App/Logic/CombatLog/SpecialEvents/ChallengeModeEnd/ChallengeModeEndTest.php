<?php

namespace Tests\Unit\App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class ChallengeModeEndTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('ChallengeModeEnd')]
    #[DataProvider('parseEvent_givenChallengeModeEndEvent_returnsCorrectValues_DataProvider')]
    public function parseEvent_givenChallengeModeEndEvent_returnsCorrectValues(
        string $challengeModeEndEvent,
        int    $expectedInstanceId,
        int    $expectedSuccess,
        int    $expectedKeystoneLevel,
        int    $expectedTotalTimeMS,
        string $expectedUnknown1,
        string $expectedUnknown2,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($challengeModeEndEvent);

        // Act
        /** @var ChallengeModeEnd $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertInstanceOf(ChallengeModeEnd::class, $combatLogEntry->getParsedEvent());
        Assert::assertEquals($expectedInstanceId, $result->getInstanceId());
        Assert::assertEquals($expectedSuccess, $result->getSuccess());
        Assert::assertEquals($expectedKeystoneLevel, $result->getKeystoneLevel());
        Assert::assertEquals($expectedTotalTimeMS, $result->getTotalTimeMS());
        Assert::assertEquals($expectedUnknown1, $result->getUnknown1());
        Assert::assertEquals($expectedUnknown2, $result->getUnknown2());
    }

    /**
     * @return array<string, list<int|string>>
     */
    public static function parseEvent_givenChallengeModeEndEvent_returnsCorrectValues_DataProvider(): array
    {
        return [
            'algethars-academy-key-7' => [
                '3/25/2026 10:58:19.7931  CHALLENGE_MODE_END,2526,1,7,1233683,270.912018,470.912018',
                2526,
                1,
                7,
                1233683,
                '270.912018',
                '470.912018',
            ],
            'pit-of-saron-key-4' => [
                '3/25/2026 10:34:12.8031  CHALLENGE_MODE_END,658,1,4,950634,200.000000,200.000000',
                658,
                1,
                4,
                950634,
                '200.000000',
                '200.000000',
            ],
        ];
    }
}
