<?php

namespace Tests\Unit\App\Logic\CombatLog\SpecialEvents\ZoneChange;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\ZoneChange;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class ZoneChangeTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('ZoneChange')]
    #[DataProvider('parseEvent_givenZoneChangeEvent_returnsCorrectValues_DataProvider')]
    public function parseEvent_givenZoneChangeEvent_returnsCorrectValues(
        string $zoneChangeEvent,
        int    $expectedZoneId,
        string $expectedZoneName,
        string $expectedDifficultyId,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($zoneChangeEvent);

        // Act
        /** @var ZoneChange $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertInstanceOf(ZoneChange::class, $combatLogEntry->getParsedEvent());
        Assert::assertEquals($expectedZoneId, $result->getZoneId());
        Assert::assertEquals($expectedZoneName, $result->getZoneName());
        Assert::assertEquals($expectedDifficultyId, $result->getDifficultyId());
    }

    public static function parseEvent_givenZoneChangeEvent_returnsCorrectValues_DataProvider(): array
    {
        return [
            'algethars-academy' => [
                '3/25/2026 10:36:28.9051  ZONE_CHANGE,2526,"Algeth\'ar Academy",23',
                2526,
                "Algeth'ar Academy",
                '23',
            ],
            'pit-of-saron' => [
                '3/25/2026 10:16:13.2761  ZONE_CHANGE,658,"Pit of Saron",23',
                658,
                'Pit of Saron',
                '23',
            ],
        ];
    }
}
