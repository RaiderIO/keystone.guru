<?php

namespace Tests\Unit\App\Logic\CombatLog\Advanced\RangeDamage;

use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedDataInterface;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\GenericData\GenericDataInterface;
use App\Logic\CombatLog\CombatEvents\Prefixes\Range;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage;
use App\Logic\CombatLog\CombatLogEntry;
use PHPUnit\Framework\Assert;
use Tests\TestCases\PublicTestCase;

class RangeDamageTest extends PublicTestCase
{

    /**
     * @test
     * @return void
     * @throws \Exception
     * @group CombatLog
     * @group RangeDamage
     * @dataProvider parseEvent_ShouldReturnAdvancedRangeDamageEvent_GivenAdvancedRangeDamageEvent_DataProvider
     */
    public function parseEvent_ShouldReturnAdvancedRangeDamageEvent_GivenAdvancedRangeDamageEvent(
        string $advancedRangeDamageEvent
    ) {
        // Arrange
        $combatLogEntry = new CombatLogEntry($advancedRangeDamageEvent);

        // Act
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent();

        // Assert
        Assert::assertInstanceOf(AdvancedCombatLogEvent::class, $combatLogEntry->getParsedEvent());
        Assert::assertInstanceOf(GenericDataInterface::class, $parseEventResult->getGenericData());
        Assert::assertInstanceOf(Range::class, $parseEventResult->getPrefix());
        Assert::assertInstanceOf(Damage::class, $parseEventResult->getSuffix());
        Assert::assertInstanceOf(AdvancedDataInterface::class, $parseEventResult->getAdvancedData());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     * @group CombatLog
     * @group RangeDamage
     * @dataProvider parseEvent_ShouldReturnValidRangeEvent_GivenAdvancedRangeDamageEvent_DataProvider
     */
    public function parseEvent_ShouldReturnValidRangeEvent_GivenAdvancedRangeDamageEvent(
        string $advancedRangeDamageEvent,
        int    $expectedSpellId,
        string $expectedSpellName,
        string $expectedSpellSchool
    ) {
        // Arrange
        $combatLogEntry = new CombatLogEntry($advancedRangeDamageEvent);

        // Act
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent();
        /** @var Range $rangeEvent */
        $rangeEvent = $parseEventResult->getPrefix();

        // Assert
        Assert::assertEquals($expectedSpellId, $rangeEvent->getSpellId());
        Assert::assertEquals($expectedSpellName, $rangeEvent->getSpellName());
        Assert::assertEquals($expectedSpellSchool, $rangeEvent->getSpellSchool());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     * @group CombatLog
     * @group RangeDamage
     * @dataProvider parseEvent_ShouldReturnValidDamageEvent_GivenAdvancedRangeDamageEvent_DataProvider
     */
    public function parseEvent_ShouldReturnValidDamageEvent_GivenAdvancedRangeDamageEvent(
        string $advancedRangeDamageEvent,
        int    $expectedAmount,
        int    $expectedRawAmount,
        int    $expectedOverKill,
        int    $expectedSchool,
        int    $expectedResisted,
        int    $expectedBlocked,
        int    $expectedAbsorbed,
        bool   $expectedIsCritical,
        bool   $expectedIsGlancing,
        bool   $expectedIsCrushing
    ) {
        // Arrange
        $combatLogEntry = new CombatLogEntry($advancedRangeDamageEvent);

        // Act
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent();
        /** @var Damage $damageEvent */
        $damageEvent = $parseEventResult->getSuffix();

        // Assert
        Assert::assertEquals($expectedAmount, $damageEvent->getAmount());
        Assert::assertEquals($expectedRawAmount, $damageEvent->getRawAmount());
        Assert::assertEquals($expectedOverKill, $damageEvent->getOverKill());
        Assert::assertEquals($expectedSchool, $damageEvent->getSchool());
        Assert::assertEquals($expectedResisted, $damageEvent->getResisted());
        Assert::assertEquals($expectedBlocked, $damageEvent->getBlocked());
        Assert::assertEquals($expectedAbsorbed, $damageEvent->getAbsorbed());
        Assert::assertEquals($expectedIsCritical, $damageEvent->isCritical());
        Assert::assertEquals($expectedIsGlancing, $damageEvent->isGlancing());
        Assert::assertEquals($expectedIsCrushing, $damageEvent->isCrushing());
    }

    public function parseEvent_ShouldReturnAdvancedRangeDamageEvent_GivenAdvancedRangeDamageEvent_DataProvider(): array
    {
        return [
            [
                '5/15 21:20:23.861  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-130909-00006285EA,"Fetid Maggot",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-130909-00006285EA,0000000000000000,980750,988005,0,0,5043,0,1,0,0,0,671.47,1235.72,1041,1.1845,70,7255,5182,-1,1,0,0,0,1,nil,nil',
            ], [
                '5/15 21:20:26.262  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-130909-00006285EA,"Fetid Maggot",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-130909-00006285EA,0000000000000000,888657,988005,0,0,5043,0,1,0,0,0,671.33,1247.24,1041,0.5010,70,3939,5625,-1,1,0,0,0,nil,nil,nil',
            ], [
                '5/15 21:20:28.934  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-130909-00006285EA,"Fetid Maggot",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-130909-00006285EA,0000000000000000,538506,988005,0,0,5043,0,1,0,0,0,677.25,1255.20,1041,0.1093,70,4074,5819,-1,1,0,0,0,nil,nil,nil',
            ], [
                '5/15 21:20:31.318  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-130909-00006285EA,"Fetid Maggot",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-130909-00006285EA,0000000000000000,383066,988005,0,0,5043,0,1,0,0,0,682.78,1255.25,1041,3.7709,70,4041,5774,-1,1,0,0,0,nil,nil,nil',
            ], [
                '5/15 21:20:36.010  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-131436-0000E285EA,"Chosen Blood Matron",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-131436-0000E285EA,0000000000000000,1294007,1580808,0,0,5043,0,1,0,0,0,673.54,1254.75,1041,3.3024,71,5665,7357,-1,1,0,0,0,nil,nil,nil',
            ],
        ];
    }

    public function parseEvent_ShouldReturnValidRangeEvent_GivenAdvancedRangeDamageEvent_DataProvider(): array
    {
        return [
            [
                '5/15 21:20:24.467  SPELL_DAMAGE,Player-1084-0A5F4542,"Paltalin-TarrenMill",0x10512,0x0,Creature-0-4242-1841-14566-131402-00026285EA,"Underrot Tick",0xa48,0x0,31935,"Avenger\'s Shield",0x2,Creature-0-4242-1841-14566-131402-00026285EA,0000000000000000,160809,197601,0,0,5043,0,1,0,0,0,666.57,1263.57,1041,3.8291,70,17069,8534,-1,2,0,0,0,1,nil,nil',
                31935,
                'Avenger\'s Shield',
                '0x2',
            ], [
                '5/15 21:20:36.010  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-131436-0000E285EA,"Chosen Blood Matron",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-131436-0000E285EA,0000000000000000,1294007,1580808,0,0,5043,0,1,0,0,0,673.54,1254.75,1041,3.3024,71,5665,7357,-1,1,0,0,0,nil,nil,nil',
                75,
                'Auto Shot',
                '0x1',
            ],
        ];
    }

    public function parseEvent_ShouldReturnValidDamageEvent_GivenAdvancedRangeDamageEvent_DataProvider(): array
    {
        return [
            [
                '5/15 21:20:23.861  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-130909-00006285EA,"Fetid Maggot",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-130909-00006285EA,0000000000000000,980750,988005,0,0,5043,0,1,0,0,0,671.47,1235.72,1041,1.1845,70,7255,5182,-1,1,0,0,0,1,nil,nil',
                7255,
                5182,
                -1,
                1,
                0,
                0,
                0,
                true,
                false,
                false,
            ], [
                '5/15 21:20:36.010  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-131436-0000E285EA,"Chosen Blood Matron",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-131436-0000E285EA,0000000000000000,1294007,1580808,0,0,5043,0,1,0,0,0,673.54,1254.75,1041,3.3024,71,5665,7357,-1,1,0,0,0,nil,nil,nil',
                5665,
                7357,
                -1,
                1,
                0,
                0,
                0,
                false,
                false,
                false,
            ],
        ];
    }
}
