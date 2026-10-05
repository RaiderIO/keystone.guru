<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatEvents\Suffixes\Damage;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\DamageInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V20\DamageV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V22\DamageV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

class DamageTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('Suffix')]
    #[Group('Damage')]
    #[DataProvider('createFromEventName_givenCombatLogVersion_returnsCorrectSuffix_dataProvider')]
    public function createFromEventName_givenCombatLogVersion_returnsCorrectSuffix(
        int    $combatLogVersion,
        string $expectedClassName,
    ): void {
        // Arrange

        // Act
        $suffix = Suffix::createFromEventName($combatLogVersion, 'DAMAGE');

        // Assert
        $this->assertSame($expectedClassName, $suffix::class);
        $this->assertInstanceOf(DamageInterface::class, $suffix);
    }

    /**
     * @return array<int, mixed>
     */
    public static function createFromEventName_givenCombatLogVersion_returnsCorrectSuffix_dataProvider(): array
    {
        return [
            [
                'combatLogVersion'  => CombatLogVersion::CLASSIC,
                'expectedClassName' => DamageV20::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::RETAIL_10_1_0,
                'expectedClassName' => DamageV20::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::RETAIL_11_0_2,
                'expectedClassName' => DamageV20::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::RETAIL_11_0_5,
                'expectedClassName' => DamageV22::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::CLASSIC_TBC_2_5_5,
                'expectedClassName' => DamageV22::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::CLASSIC_SOD_1_15_5,
                'expectedClassName' => DamageV22::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::RETAIL_12_0_5,
                'expectedClassName' => DamageV22::class,
            ],
        ];
    }

    /**
     * @param array{amount: int, rawAmount: int, overKill: int, school: int, resisted: int, blocked: int, absorbed: int, critical: bool, glancing: bool, crushing: bool, damageType: string|null} $expected
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('Suffix')]
    #[Group('Damage')]
    #[DataProvider('setParameters_givenADamageLineWithADistinctValuePerField_returnsEachFieldFromItsOwnPosition_dataProvider')]
    public function setParameters_givenADamageLineWithADistinctValuePerField_returnsEachFieldFromItsOwnPosition(
        int    $combatLogVersion,
        string $rawEvent,
        string $expectedClassName,
        array  $expected,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($rawEvent);

        // Act
        /** @var AdvancedCombatLogEvent $result */
        $result = $combatLogEntry->parseEvent([], $combatLogVersion);
        /** @var DamageV20 $suffix */
        $suffix = $result->getSuffix();

        // Assert
        $this->assertInstanceOf(AdvancedCombatLogEvent::class, $result);
        $this->assertSame($expectedClassName, $suffix::class);
        $this->assertEquals($expected['amount'], $suffix->getAmount());
        $this->assertEquals($expected['rawAmount'], $suffix->getRawAmount());
        $this->assertEquals($expected['overKill'], $suffix->getOverKill());
        $this->assertEquals($expected['school'], $suffix->getSchool());
        $this->assertEquals($expected['resisted'], $suffix->getResisted());
        $this->assertEquals($expected['blocked'], $suffix->getBlocked());
        $this->assertEquals($expected['absorbed'], $suffix->getAbsorbed());
        $this->assertSame($expected['critical'], $suffix->isCritical());
        $this->assertSame($expected['glancing'], $suffix->isGlancing());
        $this->assertSame($expected['crushing'], $suffix->isCrushing());
        if ($suffix instanceof DamageV22) {
            $this->assertSame($expected['damageType'], $suffix->getDamageType());
        }
    }

    /**
     * @return array<string, array{combatLogVersion: int, rawEvent: string, expectedClassName: class-string, expected: array<string, bool|int|string|null>}>
     */
    public static function setParameters_givenADamageLineWithADistinctValuePerField_returnsEachFieldFromItsOwnPosition_dataProvider(): array
    {
        return [
            'v20 glancing and crushing swing' => [
                'combatLogVersion'  => CombatLogVersion::RETAIL_10_1_0,
                'rawEvent'          => '5/15 21:26:51.719  SWING_DAMAGE,Player-1084-0A5F4542,"Paltalin-TarrenMill",0x10512,0x0,Creature-0-4242-1841-14566-133852-00036285EB,"Living Rot",0xa48,0x0,Player-1084-0A5F4542,0000000000000000,609800,640290,10680,2174,28198,4324,0,50000,50000,0,854.49,1031.35,1041,5.4011,410,7155,8000,-1,1,11,22,33,nil,1,1',
                'expectedClassName' => DamageV20::class,
                'expected'          => [
                    'amount'     => 7155,
                    'rawAmount'  => 8000,
                    'overKill'   => -1,
                    'school'     => 1,
                    'resisted'   => 11,
                    'blocked'    => 22,
                    'absorbed'   => 33,
                    'critical'   => false,
                    'glancing'   => true,
                    'crushing'   => true,
                    'damageType' => null,
                ],
            ],
            'v22 critical single target spell' => [
                'combatLogVersion'  => CombatLogVersion::RETAIL_12_0_1,
                'rawEvent'          => '3/25/2026 10:38:29.4491  SPELL_DAMAGE,Player-1303-09231FEC,"Riptidewave-Aggra(Português)-EU",0x512,0x80000000,Creature-0-4241-2526-8814-197398-000343ACE6,"Hungry Lasher",0xa48,0x80000000,188389,"Flame Shock",0x4,Creature-0-4241-2526-8814-197398-000343ACE6,0000000000000000,291718,446020,2494,698,4052,414,0,0,0,250000,250000,0,1801.07,-3077.86,2097,5.2480,247,17716,20000,500,4,44,55,66,1,nil,nil,ST',
                'expectedClassName' => DamageV22::class,
                'expected'          => [
                    'amount'     => 17716,
                    'rawAmount'  => 20000,
                    'overKill'   => 500,
                    'school'     => 4,
                    'resisted'   => 44,
                    'blocked'    => 55,
                    'absorbed'   => 66,
                    'critical'   => true,
                    'glancing'   => false,
                    'crushing'   => false,
                    'damageType' => 'ST',
                ],
            ],
            'v22 swing without a damage type' => [
                'combatLogVersion'  => CombatLogVersion::RETAIL_12_0_1,
                'rawEvent'          => '3/25/2026 10:38:29.4491  SWING_DAMAGE,Player-1303-09231FEC,"Riptidewave-Aggra(Português)-EU",0x512,0x80000000,Creature-0-4241-2526-8814-197398-000343ACE6,"Hungry Lasher",0xa48,0x80000000,Player-1303-09231FEC,0000000000000000,291718,446020,2494,698,4052,414,0,0,0,250000,250000,0,1801.07,-3077.86,2097,5.2480,247,3001,3002,-1,1,0,0,0,nil,1,nil',
                'expectedClassName' => DamageV22::class,
                'expected'          => [
                    'amount'     => 3001,
                    'rawAmount'  => 3002,
                    'overKill'   => -1,
                    'school'     => 1,
                    'resisted'   => 0,
                    'blocked'    => 0,
                    'absorbed'   => 0,
                    'critical'   => false,
                    'glancing'   => true,
                    'crushing'   => false,
                    'damageType' => null,
                ],
            ],
        ];
    }
}
