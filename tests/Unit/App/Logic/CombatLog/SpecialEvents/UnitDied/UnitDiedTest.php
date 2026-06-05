<?php

namespace Tests\Unit\App\Logic\CombatLog\SpecialEvents\UnitDied;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\UnitDied;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class UnitDiedTest extends PublicTestCase
{
    private const string UNIT_DIED_EVENT = '3/25/2026 10:38:49.5691  UNIT_DIED,0000000000000000,nil,0x80000000,0x80000000,Creature-0-4241-2526-8814-197398-0002C3ACE6,"Hungry Lasher",0xa48,0x80000000,0';

    #[Test]
    #[Group('CombatLog')]
    #[Group('UnitDied')]
    public function parseEvent_givenUnitDiedEvent_returnsUnitDiedInstance(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry(self::UNIT_DIED_EVENT);

        // Act
        $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertInstanceOf(UnitDied::class, $combatLogEntry->getParsedEvent());
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('UnitDied')]
    public function parseEvent_givenUnitDiedEvent_returnsNotUnconscious(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry(self::UNIT_DIED_EVENT);

        // Act
        /** @var UnitDied $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertFalse($result->isUnconsciousOnDeath());
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('UnitDied')]
    public function parseEvent_givenUnitDiedEvent_returnsCorrectDestinationName(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry(self::UNIT_DIED_EVENT);

        // Act
        /** @var UnitDied $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertEquals('Hungry Lasher', $result->getGenericData()->getDestName());
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('UnitDied')]
    public function parseEvent_givenUnitDiedEvent_returnsNullSourceGuid(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry(self::UNIT_DIED_EVENT);

        // Act
        /** @var UnitDied $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertNull($result->getGenericData()->getSourceGuid());
    }
}
