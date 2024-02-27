<?php

namespace Tests\Unit\App\Logic\CombatLog\Advanced\AdvancedData;

use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedDataInterface;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatLogEntry;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class AdvancedDataTest extends PublicTestCase
{
    /**
     * @throws \Exception
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('AdvancedData')]
    #[DataProvider('parseEvent_ShouldReturnAdvancedRangeDamageEvent_GivenAdvancedRangeDamageEvent_DataProvider')]
    public function parseEvent_ShouldReturnAdvancedData_GivenAdvancedRangeDamageEvent(
        string $advancedRangeDamageEvent
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($advancedRangeDamageEvent);

        // Act
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent();

        // Assert
        Assert::assertInstanceOf(AdvancedCombatLogEvent::class, $combatLogEntry->getParsedEvent());
        Assert::assertInstanceOf(AdvancedDataInterface::class, $parseEventResult->getAdvancedData());
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('AdvancedData')]
    #[DataProvider('parseEvent_ShouldReturnValidAdvancedData_GivenAdvancedRangeDamageEvent_DataProvider')]
    public function parseEvent_ShouldReturnValidAdvancedData_GivenAdvancedRangeDamageEvent(
        string $advancedRangeDamageEvent,
        string $expectedInfoGUID,
        ?string $expectedOwnerGUID,
        int $expectedCurrentHP,
        int $expectedMaxHP,
        int $expectedAttackPower,
        int $expectedSpellPower,
        int $expectedArmor,
        int $expectedAbsorb,
        array $expectedPowerType,
        array $expectedCurrentPower,
        array $expectedMaxPower,
        array $expectedPowerCost,
        float $expectedPositionX,
        float $expectedPositionY,
        int $expectedUiMapId,
        float $expectedFacing,
        int $expectedLevel
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($advancedRangeDamageEvent);

        // Act
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent();
        $advancedData = $parseEventResult->getAdvancedData();

        // Assert
        Assert::assertEquals($expectedInfoGUID, $advancedData->getInfoGuid());
        Assert::assertEquals($expectedOwnerGUID, $advancedData->getOwnerGuid());
        Assert::assertEquals($expectedCurrentHP, $advancedData->getCurrentHP());
        Assert::assertEquals($expectedMaxHP, $advancedData->getMaxHP());
        Assert::assertEquals($expectedAttackPower, $advancedData->getAttackPower());
        Assert::assertEquals($expectedSpellPower, $advancedData->getSpellPower());
        Assert::assertEquals($expectedArmor, $advancedData->getArmor());
        Assert::assertEquals($expectedAbsorb, $advancedData->getAbsorb());
        Assert::assertEquals($expectedPowerType, $advancedData->getPowerType());
        Assert::assertEquals($expectedCurrentPower, $advancedData->getCurrentPower());
        Assert::assertEquals($expectedMaxPower, $advancedData->getMaxPower());
        Assert::assertEquals($expectedPowerCost, $advancedData->getPowerCost());
        Assert::assertEquals($expectedPositionX, $advancedData->getPositionX());
        Assert::assertEquals($expectedPositionY, $advancedData->getPositionY());
        Assert::assertEquals($expectedUiMapId, $advancedData->getUiMapId());
        Assert::assertEquals($expectedFacing, $advancedData->getFacing());
        Assert::assertEquals($expectedLevel, $advancedData->getLevel());
    }

    public static function parseEvent_ShouldReturnAdvancedRangeDamageEvent_GivenAdvancedRangeDamageEvent_DataProvider(): array
    {
        return [
            [
                '5/15 21:20:23.861  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-130909-00006285EA,"Fetid Maggot",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-130909-00006285EA,0000000000000000,980750,988005,0,0,5043,0,1,0,0,0,671.47,1235.72,1041,1.1845,70,7255,5182,-1,1,0,0,0,1,nil,nil',
            ],
            [
                '5/15 21:20:26.262  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-130909-00006285EA,"Fetid Maggot",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-130909-00006285EA,0000000000000000,888657,988005,0,0,5043,0,1,0,0,0,671.33,1247.24,1041,0.5010,70,3939,5625,-1,1,0,0,0,nil,nil,nil',
            ],
            [
                '5/15 21:20:28.934  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-130909-00006285EA,"Fetid Maggot",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-130909-00006285EA,0000000000000000,538506,988005,0,0,5043,0,1,0,0,0,677.25,1255.20,1041,0.1093,70,4074,5819,-1,1,0,0,0,nil,nil,nil',
            ],
            [
                '5/15 21:20:31.318  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-130909-00006285EA,"Fetid Maggot",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-130909-00006285EA,0000000000000000,383066,988005,0,0,5043,0,1,0,0,0,682.78,1255.25,1041,3.7709,70,4041,5774,-1,1,0,0,0,nil,nil,nil',
            ],
            [
                '5/15 21:20:36.010  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-131436-0000E285EA,"Chosen Blood Matron",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-131436-0000E285EA,0000000000000000,1294007,1580808,0,0,5043,0,1,0,0,0,673.54,1254.75,1041,3.3024,71,5665,7357,-1,1,0,0,0,nil,nil,nil',
            ],
        ];
    }

    public static function parseEvent_ShouldReturnValidAdvancedData_GivenAdvancedRangeDamageEvent_DataProvider(): array
    {
        return [
            [
                '5/15 21:20:23.861  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-130909-00006285EA,"Fetid Maggot",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-130909-00006285EA,0000000000000000,980750,988005,0,0,5043,0,1,0,0,0,671.47,1235.72,1041,1.1845,70,7255,5182,-1,1,0,0,0,1,nil,nil',
                'Creature-0-4242-1841-14566-130909-00006285EA',
                null,
                980750,
                988005,
                0,
                0,
                5043,
                0,
                [1],
                [0],
                [0],
                [0],
                -1235.72,
                671.47,
                1041,
                1.1845,
                70,
            ],
            [
                '5/15 21:20:36.010  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-131436-0000E285EA,"Chosen Blood Matron",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-131436-0000E285EA,0000000000000000,1294007,1580808,0,0,5043,0,1,0,0,0,673.54,1254.75,1041,3.3024,71,5665,7357,-1,1,0,0,0,nil,nil,nil',
                'Creature-0-4242-1841-14566-131436-0000E285EA',
                null,
                1294007,
                1580808,
                0,
                0,
                5043,
                0,
                [1],
                [0],
                [0],
                [0],
                -1254.75,
                673.54,
                1041,
                3.3024,
                71,
            ],
        ];
    }
}
