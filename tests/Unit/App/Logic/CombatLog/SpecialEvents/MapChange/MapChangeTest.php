<?php

namespace Tests\Unit\App\Logic\CombatLog\SpecialEvents\MapChange;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\MapChange;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class MapChangeTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('MapChange')]
    public function parseEvent_givenMapChangeEvent_returnsMapChangeInstance(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry('3/25/2026 10:36:28.9051  MAP_CHANGE,2097,"Algeth\'ar Academy",2093.750000,1314.579956,-2429.169922,-3597.919922');

        // Act
        $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertInstanceOf(MapChange::class, $combatLogEntry->getParsedEvent());
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('MapChange')]
    public function parseEvent_givenMapChangeEvent_returnsCorrectUiMapId(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry('3/25/2026 10:36:28.9051  MAP_CHANGE,2097,"Algeth\'ar Academy",2093.750000,1314.579956,-2429.169922,-3597.919922');

        // Act
        /** @var MapChange $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertEquals(2097, $result->getUiMapID());
        Assert::assertEquals("Algeth'ar Academy", $result->getUiMapName());
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('MapChange')]
    public function parseEvent_givenMapChangeEvent_returnsCorrectCoordinates(): void
    {
        // Arrange
        // parameters[2]=2093.750000, parameters[3]=1314.579956, parameters[4]=-2429.169922, parameters[5]=-3597.919922
        // yMax = parameters[2] = 2093.750000
        // yMin = parameters[3] = 1314.579956
        // xMax = parameters[4] * -1 = 2429.169922
        // xMin = parameters[5] * -1 = 3597.919922
        $combatLogEntry = new CombatLogEntry('3/25/2026 10:36:28.9051  MAP_CHANGE,2097,"Algeth\'ar Academy",2093.750000,1314.579956,-2429.169922,-3597.919922');

        // Act
        /** @var MapChange $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertEqualsWithDelta(2093.750000, $result->getYMax(), 0.001);
        Assert::assertEqualsWithDelta(1314.579956, $result->getYMin(), 0.001);
        Assert::assertEqualsWithDelta(2429.169922, $result->getXMax(), 0.001);
        Assert::assertEqualsWithDelta(3597.919922, $result->getXMin(), 0.001);
    }
}
