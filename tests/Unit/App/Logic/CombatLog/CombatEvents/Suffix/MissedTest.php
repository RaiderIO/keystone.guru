<?php

namespace App\Logic\CombatLog\CombatEvents\Suffix;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\MissedInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\V20\MissedV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\V22\MissedV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\Guid\Guid;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

class MissedTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('Suffix')]
    #[Group('Missed')]
    #[DataProvider('createFromEventName_givenCombatLogVersion_returnsCorrectSuffix_dataProvider')]
    public function createFromEventName_givenCombatLogVersion_returnsCorrectSuffix(
        int    $combatLogVersion,
        string $expectedClassName
    ): void {
        // Arrange


        // Act
        $suffix = Suffix::createFromEventName($combatLogVersion, 'MISSED');

        // Assert
        $this->assertInstanceOf($expectedClassName, $suffix);
        $this->assertInstanceOf(MissedInterface::class, $suffix);
    }

    public static function createFromEventName_givenCombatLogVersion_returnsCorrectSuffix_dataProvider(): array
    {
        return [
            [
                'combatLogVersion'  => CombatLogVersion::CLASSIC,
                'expectedClassName' => MissedV20::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::RETAIL_10_1_0,
                'expectedClassName' => MissedV20::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::RETAIL_11_0_2,
                'expectedClassName' => MissedV20::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::RETAIL_11_0_5,
                'expectedClassName' => MissedV22::class,
            ],
        ];
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('Suffix')]
    #[Group('Missed')]
    #[DataProvider('setParameters_givenCombatLogLineV20_shouldParseParametersCorrectly_dataProvider')]
    public function setParameters_givenCombatLogLineV20_shouldParseParametersCorrectly(
        string $combatLogLine,
        Guid   $missType,
        bool   $offhand,
        int    $amountMissed,
        int    $amountTotal,
        bool   $critical,
    ): void {
        // Arrange

        // Act
        /** @var AdvancedCombatLogEvent $missedEvent */
        $missedEvent = (new CombatLogEntry($combatLogLine))->parseEvent([], CombatLogVersion::RETAIL_10_1_0);

        // Assert
        $this->assertInstanceOf(MissedV20::class, $missedEvent->getSuffix());
        $this->assertInstanceOf(MissedInterface::class, $missedEvent->getSuffix());
        /** @var MissedInterface $missedSuffix */
        $missedSuffix = $missedEvent->getSuffix();
        $this->assertEquals($missType, $missedSuffix->getMissType());
        $this->assertEquals($offhand, $missedSuffix->isOffhand());
        $this->assertEquals($amountMissed, $missedSuffix->getAmountMissed());
        $this->assertEquals($amountTotal, $missedSuffix->getAmountTotal());
        $this->assertEquals($critical, $missedSuffix->isCritical());
        $this->assertNull($missedSuffix->getDamageType());
    }

    public static function setParameters_givenCombatLogLineV20_shouldParseParametersCorrectly_dataProvider(): array
    {
        $guidChecks = [];
        foreach (Guid::GUID_MISS_TYPES as $type => $class) {
            $guidChecks[sprintf('Guid check %s', $type)] = [
                'combatLogLine' => sprintf('8/18/2024 14:24:27.4442  SPELL_MISSED,Creature-0-2085-2662-31810-228537-000041E7F7,"Nightfall Shadowalker",0xa48,0x0,Player-4184-00C9CE4F,"Isak-TheseGoToEleven-TR",0x511,0x20,431638,"Umbral Rush",0x20,%s,nil', $type),
                'missType'      => Guid::createFromGuidString($type),
                'offhand'       => false,
                'amountMissed'  => 0,
                'amountTotal'   => 0,
                'critical'      => false,
            ];
        }

        return array_merge($guidChecks, [
            'Offhand check'         => [
                'combatLogLine' => '8/18/2024 14:24:27.4442  SPELL_MISSED,Creature-0-2085-2662-31810-228537-000041E7F7,"Nightfall Shadowalker",0xa48,0x0,Player-4184-00C9CE4F,"Isak-TheseGoToEleven-TR",0x511,0x20,431638,"Umbral Rush",0x20,MISS,1',
                'missType'      => Guid::createFromGuidString('MISS'),
                'offhand'       => true,
                'amountMissed'  => 0,
                'amountTotal'   => 0,
                'critical'      => false,
            ],
            'Absorb check'          => [
                'combatLogLine' => '9/29/2024 09:50:17.5901  SPELL_MISSED,Creature-0-4241-2669-4019-220195-0004F91421,"Sureki Silkbinder",0xa48,0x0,Player-1598-0F46FFFD,"Renzyzard-Sunstrider-EU",0x10512,0x0,443427,"Web Bolt",0x8,ABSORB,nil,2594650,2785099,nil',
                'missType'      => Guid::createFromGuidString('ABSORB'),
                'offhand'       => false,
                'amountMissed'  => 2594650,
                'amountTotal'   => 2785099,
                'critical'      => false,
            ],
            'Critical strike check' => [
                'combatLogLine' => '9/29/2024 09:50:17.5901  SPELL_MISSED,Creature-0-4241-2669-4019-220195-0004F91421,"Sureki Silkbinder",0xa48,0x0,Player-1598-0F46FFFD,"Renzyzard-Sunstrider-EU",0x10512,0x0,443427,"Web Bolt",0x8,ABSORB,nil,2594650,2785099,1',
                'missType'      => Guid::createFromGuidString('ABSORB'),
                'offhand'       => false,
                'amountMissed'  => 2594650,
                'amountTotal'   => 2785099,
                'critical'      => true,
            ],
        ]);
    }


    #[Test]
    #[Group('CombatLog')]
    #[Group('Suffix')]
    #[Group('Missed')]
    #[DataProvider('setParameters_givenCombatLogLineV22_shouldParseParametersCorrectly_dataProvider')]
    public function setParameters_givenCombatLogLineV22_shouldParseParametersCorrectly(
        string  $combatLogLine,
        Guid    $missType,
        bool    $offhand,
        int     $amountMissed,
        int     $amountTotal,
        bool    $critical,
        ?string $damageType
    ): void {
        // Arrange

        // Act
        /** @var AdvancedCombatLogEvent $missedEvent */
        $missedEvent = (new CombatLogEntry($combatLogLine))->parseEvent([], CombatLogVersion::RETAIL_11_0_5);

        // Assert
        $this->assertInstanceOf(MissedV22::class, $missedEvent->getSuffix());
        $this->assertInstanceOf(MissedInterface::class, $missedEvent->getSuffix());
        /** @var MissedInterface $missedSuffix */
        $missedSuffix = $missedEvent->getSuffix();
        $this->assertEquals($missType, $missedSuffix->getMissType());
        $this->assertEquals($offhand, $missedSuffix->isOffhand());
        $this->assertEquals($amountMissed, $missedSuffix->getAmountMissed());
        $this->assertEquals($amountTotal, $missedSuffix->getAmountTotal());
        $this->assertEquals($critical, $missedSuffix->isCritical());
        $this->assertEquals($damageType, $missedSuffix->getDamageType());
    }

    public static function setParameters_givenCombatLogLineV22_shouldParseParametersCorrectly_dataProvider(): array
    {
        $guidChecks = [];
        foreach (Guid::GUID_MISS_TYPES as $type => $class) {
            $guidChecks[sprintf('Guid check %s', $type)] = [
                'combatLogLine' => sprintf('11/9/2024 23:12:43.2561  SPELL_MISSED,Player-1084-0B0A5965,"Wolflocks-TarrenMill-EU",0x512,0x0,Creature-0-4251-2662-13168-214761-00002FDE4F,"Nightfall Ritualist",0xa48,0x80,30283,"Shadowfury",0x20,%s,nil,AOE', $type),
                'missType'      => Guid::createFromGuidString($type),
                'offhand'       => false,
                'amountMissed'  => 0,
                'amountTotal'   => 0,
                'critical'      => false,
                'damageType'    => 'AOE',
            ];
        }

        return array_merge($guidChecks, [
            'ST check'              => [
                'combatLogLine' => '11/9/2024 23:12:43.2561  SPELL_MISSED,Player-1084-0B0A5965,"Wolflocks-TarrenMill-EU",0x512,0x0,Creature-0-4251-2662-13168-214761-00002FDE4F,"Nightfall Ritualist",0xa48,0x80,30283,"Shadowfury",0x20,IMMUNE,1,ST',
                'missType'      => Guid::createFromGuidString('IMMUNE'),
                'offhand'       => true,
                'amountMissed'  => 0,
                'amountTotal'   => 0,
                'critical'      => false,
                'damageType'    => 'ST',
            ],
            'Offhand check'         => [
                'combatLogLine' => '11/9/2024 23:12:43.2561  SPELL_MISSED,Player-1084-0B0A5965,"Wolflocks-TarrenMill-EU",0x512,0x0,Creature-0-4251-2662-13168-214761-00002FDE4F,"Nightfall Ritualist",0xa48,0x80,30283,"Shadowfury",0x20,IMMUNE,1,AOE',
                'missType'      => Guid::createFromGuidString('IMMUNE'),
                'offhand'       => true,
                'amountMissed'  => 0,
                'amountTotal'   => 0,
                'critical'      => false,
                'damageType'    => 'AOE',
            ],
            'Absorb check'          => [
                'combatLogLine' => '11/9/2024 23:13:39.5971  SPELL_MISSED,Creature-0-4251-2662-13168-214762-00002FDE81,"Nightfall Commander",0xa48,0x1,Player-1096-0AC6D32F,"Andy-Ravenholdt-EU",0x512,0x0,450756,"Abyssal Howl",0x20,ABSORB,nil,559824,745709,nil,AOE',
                'missType'      => Guid::createFromGuidString('ABSORB'),
                'offhand'       => false,
                'amountMissed'  => 559824,
                'amountTotal'   => 745709,
                'critical'      => false,
                'damageType'    => 'AOE',
            ],
            'Absorb ST check'       => [
                'combatLogLine' => '11/9/2024 23:13:39.5971  SPELL_MISSED,Creature-0-4251-2662-13168-214762-00002FDE81,"Nightfall Commander",0xa48,0x1,Player-1096-0AC6D32F,"Andy-Ravenholdt-EU",0x512,0x0,450756,"Abyssal Howl",0x20,ABSORB,nil,559824,745709,nil,ST',
                'missType'      => Guid::createFromGuidString('ABSORB'),
                'offhand'       => false,
                'amountMissed'  => 559824,
                'amountTotal'   => 745709,
                'critical'      => false,
                'damageType'    => 'ST',
            ],
            'Critical strike check' => [
                'combatLogLine' => '11/9/2024 23:13:39.5971  SPELL_MISSED,Creature-0-4251-2662-13168-214762-00002FDE81,"Nightfall Commander",0xa48,0x1,Player-1096-0AC6D32F,"Andy-Ravenholdt-EU",0x512,0x0,450756,"Abyssal Howl",0x20,ABSORB,nil,559824,745709,1,AOE',
                'missType'      => Guid::createFromGuidString('ABSORB'),
                'offhand'       => false,
                'amountMissed'  => 559824,
                'amountTotal'   => 745709,
                'critical'      => true,
                'damageType'    => 'AOE',
            ],
            'Swing'                 => [
                'combatLogLine' => '11/9/2024 23:12:35.9441  SWING_MISSED,Creature-0-4251-2662-13168-213892-00002FDE4F,"Nightfall Shadowmage",0xa48,0x1,Player-1403-09A74524,"Llewéllyn-Draenor-EU",0x512,0x20,PARRY,nil',
                'missType'      => Guid::createFromGuidString('PARRY'),
                'offhand'       => false,
                'amountMissed'  => 0,
                'amountTotal'   => 0,
                'critical'      => false,
                'damageType'    => null,
            ],
        ]);
    }
}