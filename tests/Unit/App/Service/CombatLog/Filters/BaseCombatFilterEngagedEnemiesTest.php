<?php

namespace Tests\Unit\App\Service\CombatLog\Filters;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Service\CombatLog\Filters\MappingVersion\CombatFilter;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('BaseCombatFilter')]
final class BaseCombatFilterEngagedEnemiesTest extends PublicTestCase
{
    private const string ZONE_CHANGE = '3/7/2026 21:12:36.6071  ZONE_CHANGE,2859,"The Blinding Vale",23';

    private const string ALIVE_GUID = 'Creature-0-4252-2859-51410-245336-00022C86B2';

    private const string KILLED_GUID = 'Creature-0-4252-2859-51410-245336-00022C86B3';

    private const string SUMMONED_GUID = 'Creature-0-4252-2859-51410-245336-00022C86B4';

    #[Test]
    public function getEngagedEnemies_givenAliveKilledAndSummonedEnemies_returnsOnlyTheAliveOneWithItsFirstSighting(): void
    {
        // Arrange
        $filter = new CombatFilter(collect());

        // Act
        $this->feed($filter, [
            self::ZONE_CHANGE,
            $this->spellDamage('21:14:55.1021', self::ALIVE_GUID),
            $this->spellDamage('21:14:56.1021', self::KILLED_GUID),
            '3/7/2026 21:14:57.1021  SPELL_SUMMON,Creature-0-4252-2859-51410-245336-00022C86B2,"Radiant Spellsower",0x10a48,0x0,'
            . self::SUMMONED_GUID . ',"Radiant Spellsower",0xa28,0x0,52174,"Heroic Leap",0x1',
            $this->spellDamage('21:14:58.1021', self::SUMMONED_GUID),
            $this->spellDamage('21:15:05.1021', self::ALIVE_GUID),
            '3/7/2026 21:15:11.2031  UNIT_DIED,0000000000000000,nil,0x80000000,0x80000000,' . self::KILLED_GUID . ',"Radiant Spellsower",0xa48,0x80000000,0',
        ]);
        $engagedEnemies = $filter->getEngagedEnemies();

        // Assert
        $this->assertSame([self::ALIVE_GUID], $engagedEnemies->keys()->all());
        $this->assertSame(
            '2026-03-07 21:14:55',
            $engagedEnemies->get(self::ALIVE_GUID)->getTimestamp()->toDateTimeString(),
            'The engaged enemy must carry the event it was first seen in, as its EnemyEngaged would',
        );
    }

    /**
     * A player damaging a creature - the advanced data's info GUID is what puts the creature in combat.
     */
    private function spellDamage(string $time, string $creatureGuid): string
    {
        return sprintf(
            '3/7/2026 %s  SPELL_DAMAGE,Player-633-0A64192A,"Virgilus-Darkspear-EU",0x512,0x80000000,'
            . '%s,"Radiant Spellsower",0x10a48,0x80000000,52174,"Heroic Leap",0x1,'
            . '%s,0000000000000000,943271,943740,0,0,1176,0,0,0,0,21262,21262,0,'
            . '1305.82,2076.09,2500,5.3831,90,469,629,-1,1,0,0,0,nil,nil,nil,AOE',
            $time,
            $creatureGuid,
            $creatureGuid,
        );
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
