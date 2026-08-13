<?php

namespace Tests\Unit\App\Service\CombatLog\Filters;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Service\CombatLog\Filters\MappingVersion\CombatFilter;
use App\Service\CombatLog\ResultEvents\EnemyEngaged;
use App\Service\CombatLog\ResultEvents\EnemyKilled;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * An enemy death arrives as a UNIT_DIED/PARTY_KILL special event, which extends GenericSpecialEvent
 * rather than CombatLogEvent. Type checking the death branch against CombatLogEvent alone silently
 * discards every enemy kill and leaves the auto route creator with an empty route.
 */
#[Group('CombatLog')]
#[Group('BaseCombatFilter')]
class BaseCombatFilterEnemyDeathTest extends PublicTestCase
{
    private const string ZONE_CHANGE = '3/7/2026 21:12:36.6071  ZONE_CHANGE,2859,"The Blinding Vale",23';

    /** A player damaging a creature - the advanced data's info GUID is what puts the creature in combat. */
    private const string SPELL_DAMAGE = '3/7/2026 21:14:55.1021  SPELL_DAMAGE,Player-633-0A64192A,"Virgilus-Darkspear-EU",0x512,0x80000000,'
        . 'Creature-0-4252-2859-51410-245336-00022C86B2,"Radiant Spellsower",0x10a48,0x80000000,52174,"Heroic Leap",0x1,'
        . 'Creature-0-4252-2859-51410-245336-00022C86B2,0000000000000000,943271,943740,0,0,1176,0,0,0,0,21262,21262,0,'
        . '1305.82,2076.09,2500,5.3831,90,469,629,-1,1,0,0,0,nil,nil,nil,AOE';

    private const string UNIT_DIED = '3/7/2026 21:15:11.2031  UNIT_DIED,0000000000000000,nil,0x80000000,0x80000000,'
        . 'Creature-0-4252-2859-51410-245336-00022C86B2,"Radiant Spellsower",0xa48,0x80000000,0';

    private const string PARTY_KILL = '3/7/2026 21:15:11.2011  PARTY_KILL,Player-1084-05D21C13,"Noctus-TarrenMill-EU",0x512,0x80000000,'
        . 'Creature-0-4252-2859-51410-245336-00022C86B2,"Radiant Spellsower",0xa48,0x80000000,0';

    #[Test]
    public function parse_givenEngagedEnemyThatUnitDied_returnsEnemyEngagedAndEnemyKilled(): void
    {
        // Arrange
        $resultEvents = collect();
        $filter       = new CombatFilter($resultEvents);

        // Act
        $this->feed($filter, [self::ZONE_CHANGE, self::SPELL_DAMAGE, self::UNIT_DIED]);

        // Assert
        $this->assertCount(1, $resultEvents->filter(static fn($e) => $e instanceof EnemyEngaged));
        $this->assertCount(1, $resultEvents->filter(static fn($e) => $e instanceof EnemyKilled));
    }

    #[Test]
    public function parse_givenEngagedEnemyThatPartyKilled_returnsEnemyEngagedAndEnemyKilled(): void
    {
        // Arrange
        $resultEvents = collect();
        $filter       = new CombatFilter($resultEvents);

        // Act
        $this->feed($filter, [self::ZONE_CHANGE, self::SPELL_DAMAGE, self::PARTY_KILL]);

        // Assert
        $this->assertCount(1, $resultEvents->filter(static fn($e) => $e instanceof EnemyEngaged));
        $this->assertCount(1, $resultEvents->filter(static fn($e) => $e instanceof EnemyKilled));
    }

    #[Test]
    public function parse_givenEnemyThatDiedWithoutBeingEngaged_returnsNoResultEvents(): void
    {
        // Arrange
        $resultEvents = collect();
        $filter       = new CombatFilter($resultEvents);

        // Act
        $this->feed($filter, [self::ZONE_CHANGE, self::UNIT_DIED]);

        // Assert
        $this->assertCount(0, $resultEvents);
    }

    /**
     * @param string[] $rawEvents
     *
     * @throws Exception
     */
    private function feed(CombatFilter $filter, array $rawEvents): void
    {
        foreach ($rawEvents as $lineNr => $rawEvent) {
            $parsedEvent = new CombatLogEntry($rawEvent)->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

            if ($parsedEvent !== null) {
                $filter->parse($parsedEvent, $lineNr + 1);
            }
        }
    }
}
