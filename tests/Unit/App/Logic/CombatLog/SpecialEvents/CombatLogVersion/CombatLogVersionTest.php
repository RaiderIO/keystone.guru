<?php

namespace Tests\Unit\App\Logic\CombatLog\SpecialEvents\CombatLogVersion;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\CombatLogVersion as CombatLogVersionEvent;
use App\Logic\CombatLog\SpecialEvents\Interfaces\HasCombatLogVersionInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('CombatLogVersion')]
final class CombatLogVersionTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('getVersionLong_givenCombatLogVersionEvent_returnsCorrectVersionLong_DataProvider')]
    public function getVersionLong_givenCombatLogVersionEvent_returnsCorrectVersionLong(
        string $rawEvent,
        int    $expectedVersionLong,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($rawEvent);

        // Act
        /** @var CombatLogVersionEvent $result */
        $result = $combatLogEntry->parseEvent([], $expectedVersionLong);

        // Assert
        Assert::assertInstanceOf(HasCombatLogVersionInterface::class, $result);
        Assert::assertEquals($expectedVersionLong, $result->getVersionLong());
    }

    public static function getVersionLong_givenCombatLogVersionEvent_returnsCorrectVersionLong_DataProvider(): array
    {
        return [
            'retail-10-1-0' => [
                '5/15 21:20:10.941  COMBAT_LOG_VERSION,20,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,10.1.0,PROJECT_ID,1',
                CombatLogVersion::RETAIL_10_1_0,
            ],
            'retail-12-0-5' => [
                '5/31/2026 22:00:00.0000  COMBAT_LOG_VERSION,22,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,12.0.5,PROJECT_ID,1',
                CombatLogVersion::RETAIL_12_0_5,
            ],
        ];
    }
}
