<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatEvents\Suffix;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageSupport\DamageSupportInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageSupport\V20\DamageSupportV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageSupport\V22\DamageSupportV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

class DamageSupportTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('Suffix')]
    #[Group('DamageSupport')]
    #[DataProvider('createFromEventName_givenCombatLogVersion_returnsCorrectSuffix_dataProvider')]
    public function createFromEventName_givenCombatLogVersion_returnsCorrectSuffix(
        int    $combatLogVersion,
        string $expectedClassName,
    ): void {
        // Arrange

        // Act
        $suffix = Suffix::createFromEventName($combatLogVersion, 'DAMAGE_SUPPORT');

        // Assert
        $this->assertInstanceOf($expectedClassName, $suffix);
        $this->assertInstanceOf(DamageSupportInterface::class, $suffix);
    }

    public static function createFromEventName_givenCombatLogVersion_returnsCorrectSuffix_dataProvider(): array
    {
        return [
            [
                'combatLogVersion'  => CombatLogVersion::CLASSIC,
                'expectedClassName' => DamageSupportV20::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::RETAIL_10_1_0,
                'expectedClassName' => DamageSupportV20::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::RETAIL_11_0_2,
                'expectedClassName' => DamageSupportV20::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::RETAIL_11_0_5,
                'expectedClassName' => DamageSupportV22::class,
            ],
        ];
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('Suffix')]
    #[Group('DamageSupport')]
    #[DataProvider('setParameters_givenValidEvent_shouldReturnCorrectValues_dataProvider')]
    public function setParameters_givenValidEvent_shouldReturnCorrectValues(
        int    $combatLogVersion,
        string $rawEvent,
        array  $expectedValues,
    ): void {
        // Arrange

        // Act
        /** @var AdvancedCombatLogEvent $advancedCombatLogEvent */
        $advancedCombatLogEvent = (new CombatLogEntry($rawEvent))->parseEvent([], $combatLogVersion);
        /** @var DamageSupportV22 $suffix */
        $suffix = $advancedCombatLogEvent->getSuffix();

        // Assert
        $this->assertInstanceOf(DamageSupportV22::class, $suffix);
        $this->assertEquals($expectedValues['supportGuid'], $suffix->getSupportGuid());
    }

    public static function setParameters_givenValidEvent_shouldReturnCorrectValues_dataProvider(): array
    {
        return [
            [
                'combatLogVersion' => CombatLogVersion::RETAIL_11_0_5,
                'rawEvent'         => '11/24/2024 13:01:06.0290  SPELL_DAMAGE_SUPPORT,Player-1084-0A2CAD7D,"Krabix-TarrenMill-EU",0x511,0x0,Creature-0-4247-2290-7331-165111-000143237F,"Drust Spiteclaw",0xa48,0x0,360828,"Blistering Scales",0xc,Creature-0-4247-2290-7331-165111-000143237F,0000000000000000,49136123,49467365,0,0,42857,0,0,0,1,0,0,0,-6979.75,1878.09,1669,0.9612,80,43331,21240,-1,12,0,0,0,1,nil,nil,Player-1401-0A4CFE3A',
                'expectedValues'   => [
                    'supportGuid' => 'Player-1401-0A4CFE3A',
                ],
            ],
        ];
    }
}
