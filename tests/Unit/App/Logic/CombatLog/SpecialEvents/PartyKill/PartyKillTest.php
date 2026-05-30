<?php

namespace Tests\Unit\App\Logic\CombatLog\SpecialEvents\PartyKill;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\Guid\Player;
use App\Logic\CombatLog\SpecialEvents\PartyKill;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class PartyKillTest extends PublicTestCase
{
    private const string PARTY_KILL_EVENT = '3/25/2026 10:38:49.5691  PARTY_KILL,Player-3674-0B71E4D2,"Whisker-TwistingNether-EU",0x512,0x80000000,Creature-0-4241-2526-8814-197398-0002C3ACE6,"Hungry Lasher",0xa48,0x80000000,0';

    #[Test]
    #[Group('CombatLog')]
    #[Group('PartyKill')]
    public function parseEvent_givenPartyKillEvent_returnsPartyKillInstance(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry(self::PARTY_KILL_EVENT);

        // Act
        $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertInstanceOf(PartyKill::class, $combatLogEntry->getParsedEvent());
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('PartyKill')]
    public function parseEvent_givenPartyKillEvent_returnsCorrectSourceName(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry(self::PARTY_KILL_EVENT);

        // Act
        /** @var PartyKill $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertEquals('Whisker-TwistingNether-EU', $result->getGenericData()->getSourceName());
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('PartyKill')]
    public function parseEvent_givenPartyKillEvent_returnsCorrectDestName(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry(self::PARTY_KILL_EVENT);

        // Act
        /** @var PartyKill $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertEquals('Hungry Lasher', $result->getGenericData()->getDestName());
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('PartyKill')]
    public function parseEvent_givenPartyKillEvent_sourceGuidIsPlayer(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry(self::PARTY_KILL_EVENT);

        // Act
        /** @var PartyKill $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertInstanceOf(Player::class, $result->getGenericData()->getSourceGuid());
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('PartyKill')]
    public function parseEvent_givenPartyKillEvent_isNotUnconscious(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry(self::PARTY_KILL_EVENT);

        // Act
        /** @var PartyKill $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertFalse($result->isUnconsciousOnDeath());
    }
}
