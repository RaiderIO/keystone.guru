<?php

namespace Tests\Unit\App\Service\CombatLog\Filters;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Service\CombatLog\Filters\MappingVersion\CombatFilter;
use App\Service\CombatLog\ResultEvents\EnemyEngaged;
use App\Service\CombatLog\ResultEvents\EnemyKilled;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Some bosses turn friendly at 1 HP instead of dying, so no UNIT_DIED for them ever reaches the filter. The lines below
 * are taken from real logs (runs 25921810 and 25921879) - the first hit of the pull and the last one before the boss
 * turned friendly. The last hit's current HP is swapped for one just above 1% to show where the threshold lies.
 */
#[Group('CombatLog')]
#[Group('BaseCombatFilter')]
class BaseCombatFilterDefeatedPercentageTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('bossTurnsFriendlyAtOneHpProvider')]
    public function parse_givenBossDamagedBelowOnePercent_returnsEnemyKilled(
        string $zoneChange,
        string $firstHit,
        string $lastHit,
        string $currentHpOfLastHit,
        string $currentHpJustAboveOnePercent,
    ): void {
        // Arrange
        $resultEvents = collect();
        $filter       = new CombatFilter($resultEvents);

        // Act
        $this->feed($filter, [$zoneChange, $firstHit, $lastHit]);

        // Assert
        $this->assertCount(1, $resultEvents->filter(static fn($e) => $e instanceof EnemyEngaged));
        $this->assertCount(1, $resultEvents->filter(static fn($e) => $e instanceof EnemyKilled));
    }

    #[Test]
    #[DataProvider('bossTurnsFriendlyAtOneHpProvider')]
    public function parse_givenBossDamagedToJustAboveOnePercent_returnsNoResultEvents(
        string $zoneChange,
        string $firstHit,
        string $lastHit,
        string $currentHpOfLastHit,
        string $currentHpJustAboveOnePercent,
    ): void {
        // Arrange
        $resultEvents  = collect();
        $filter        = new CombatFilter($resultEvents);
        $almostLastHit = str_replace($currentHpOfLastHit, $currentHpJustAboveOnePercent, $lastHit);

        // Act
        $this->feed($filter, [$zoneChange, $firstHit, $almostLastHit]);

        // Assert
        $this->assertCount(0, $resultEvents);
    }

    /**
     * @return array<string, array{string, string, string, string, string}>
     */
    public static function bossTurnsFriendlyAtOneHpProvider(): array
    {
        return [
            'Den of Nalorakk: Nalorakk' => [
                '11/17/2026 22:27:40.0000  ZONE_CHANGE,2825,"Den of Nalorakk",23',
                '11/17/2026 22:27:49.9428  SPELL_DAMAGE,Player-886-064ECCB5,"戰临丶-血色十字军-CN",0x511,0x80000002,'
                . 'Creature-0-3045-2825-244539-246404-00007C5E5D,"纳洛拉克",0x10a48,0x80000000,195292,"死神的抚摩",0x20,'
                . 'Creature-0-3045-2825-244539-246404-00007C5E5D,0000000000000000,166812893,166816826,0,0,1470,0,0,0,3,0,100,0,'
                . '5013.81,-4841.23,2513,2.2750,92,2474,2474,-1,32,0,0,0,nil,nil,nil,ST',
                '11/17/2026 22:31:46.3748  SPELL_DAMAGE,Player-886-064ECCB5,"戰临丶-血色十字军-CN",0x511,0x80000002,'
                . 'Creature-0-3045-2825-244539-246404-00007C5E5D,"纳洛拉克",0x10a48,0x80000000,50842,"血液沸腾",0x20,'
                . 'Creature-0-3045-2825-244539-246404-00007C5E5D,0000000000000000,139168,166816826,0,0,1470,0,0,0,3,73,100,0,'
                . '4998.53,-4839.99,2513,3.7039,92,11623,10542,-1,32,0,0,0,nil,nil,nil,AOE',
                ',139168,166816826,',
                ',1700000,166816826,',
            ],
            // Zul'jan is logged as a Vehicle rather than a Creature
            'Altar of Fangs: Zul\'jan' => [
                '11/17/2026 09:29:50.000-5  ZONE_CHANGE,2993,"Altar of Fangs",23',
                '11/17/2026 09:30:01.031-5  SPELL_DAMAGE,Player-1427-0EE446D2,"Nostradamuxs-Ragnaros-US",0x512,0x80000000,'
                . 'Vehicle-0-3886-2993-186744-259447-00007C5ECC,"祖尔加",0x10a48,0x80000000,52212,"枯萎凋零",0x20,'
                . 'Vehicle-0-3886-2993-186744-259447-00007C5ECC,0000000000000000,59674835,59675891,0,0,1470,0,0,0,3,100,100,0,'
                . '1428.98,2115.18,2590,3.1402,92,1056,1056,-1,32,0,0,0,nil,nil,nil,AOE',
                '11/17/2026 09:32:12.016-5  SPELL_DAMAGE,Player-3683-0D62E63E,"Scapeone-Dalaran-US",0x511,0x80000000,'
                . 'Vehicle-0-3886-2993-186744-259447-00007C5ECC,"祖尔加",0x10a48,0x80000000,383328,"最终审判",0x2,'
                . 'Vehicle-0-3886-2993-186744-259447-00007C5ECC,0000000000000000,86194,59675891,0,0,1470,0,0,0,3,100,100,0,'
                . '1423.24,2139.42,2590,2.4922,92,149521,55368,-1,2,0,0,0,1,nil,nil,ST',
                ',86194,59675891,',
                ',610000,59675891,',
            ],
        ];
    }

    /**
     * @param string[] $rawEvents
     *
     * @throws Exception
     */
    private function feed(CombatFilter $filter, array $rawEvents): void
    {
        foreach ($rawEvents as $lineNr => $rawEvent) {
            $parsedEvent = new CombatLogEntry($rawEvent)->parseEvent([], CombatLogVersion::RETAIL_12_1_5);

            if ($parsedEvent !== null) {
                $filter->parse($parsedEvent, $lineNr + 1);
            }
        }
    }
}
