<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatEvents\Advanced\AdvancedData;

use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedDataInterface;
use App\Logic\CombatLog\CombatEvents\Advanced\Versions\V22\AdvancedDataV22;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCases\PublicTestCase;

final class AdvancedDataTest extends PublicTestCase
{
    private const string RAW_ADVANCED_RANGE_DAMAGE_EVENT = '5/15 21:20:23.861  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-130909-00006285EA,"Fetid Maggot",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-130909-00006285EA,0000000000000000,980750,988005,0,0,5043,0,1,0,0,0,671.47,1235.72,1041,1.1845,70,7255,5182,-1,1,0,0,0,1,nil,nil';

    /**
     * @throws \Exception
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('AdvancedData')]
    #[DataProvider('parseEvent_ShouldReturnAdvancedRangeDamageEvent_GivenAdvancedRangeDamageEvent_DataProvider')]
    public function parseEvent_ShouldReturnAdvancedData_GivenAdvancedRangeDamageEvent(
        string $advancedRangeDamageEvent,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($advancedRangeDamageEvent);

        // Act
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_10_1_0);

        // Assert
        Assert::assertInstanceOf(AdvancedCombatLogEvent::class, $combatLogEntry->getParsedEvent());
        Assert::assertInstanceOf(AdvancedDataInterface::class, $parseEventResult->getAdvancedData());
    }

    /**
     * @throws \Exception
     *
     * @param array<int, mixed> $expectedPowerType
     * @param array<int, mixed> $expectedCurrentPower
     * @param array<int, mixed> $expectedMaxPower
     * @param array<int, mixed> $expectedPowerCost
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('AdvancedData')]
    #[DataProvider('parseEvent_ShouldReturnValidAdvancedData_GivenAdvancedRangeDamageEvent_DataProvider')]
    public function parseEvent_ShouldReturnValidAdvancedData_GivenAdvancedRangeDamageEvent(
        string  $advancedRangeDamageEvent,
        string  $expectedInfoGUID,
        ?string $expectedOwnerGUID,
        int     $expectedCurrentHP,
        int     $expectedMaxHP,
        int     $expectedAttackPower,
        int     $expectedSpellPower,
        int     $expectedArmor,
        int     $expectedAbsorb,
        array   $expectedPowerType,
        array   $expectedCurrentPower,
        array   $expectedMaxPower,
        array   $expectedPowerCost,
        float   $expectedPositionX,
        float   $expectedPositionY,
        int     $expectedUiMapId,
        float   $expectedFacing,
        int     $expectedLevel,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($advancedRangeDamageEvent);

        // Act
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_10_1_0);
        $advancedData     = $parseEventResult->getAdvancedData();

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

    /**
     * @throws \Exception
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('AdvancedData')]
    public function getAdvancedData_givenNoGetterCalled_neverParsesLazyFields(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry(self::RAW_ADVANCED_RANGE_DAMAGE_EVENT);

        // Act
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_10_1_0);
        $advancedData     = $parseEventResult->getAdvancedData();

        // Assert - retrieving the interface itself must not trigger parsing of any lazy field
        Assert::assertFalse($this->guidHasBeenParsed($advancedData, 'infoGuid'));
        Assert::assertFalse($this->guidHasBeenParsed($advancedData, 'ownerGuid'));
        Assert::assertFalse($this->powerArrayHasBeenParsed($advancedData, 'powerType'));
        Assert::assertFalse($this->powerArrayHasBeenParsed($advancedData, 'currentPower'));
        Assert::assertFalse($this->powerArrayHasBeenParsed($advancedData, 'maxPower'));
        Assert::assertFalse($this->powerArrayHasBeenParsed($advancedData, 'powerCost'));
    }

    /**
     * @throws \Exception
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('AdvancedData')]
    public function getInfoGuid_calledTwice_parsesOnceAndReturnsTheSameInstance(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry(self::RAW_ADVANCED_RANGE_DAMAGE_EVENT);
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_10_1_0);
        $advancedData     = $parseEventResult->getAdvancedData();

        // Act
        $first = $advancedData->getInfoGuid();
        Assert::assertTrue($this->guidHasBeenParsed($advancedData, 'infoGuid'));
        $second = $advancedData->getInfoGuid();

        // Assert - the second call must not re-parse, it should return the exact same instance;
        // and parsing the info GUID must not have dragged the other lazy fields along with it
        Assert::assertSame($first, $second);
        Assert::assertFalse($this->guidHasBeenParsed($advancedData, 'ownerGuid'));
        Assert::assertFalse($this->powerArrayHasBeenParsed($advancedData, 'powerType'));
    }

    /**
     * @throws \Exception
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('AdvancedData')]
    public function getInfoGuidRaw_calledBeforeAndAfterGuidParsing_returnsTheSameRawGuidString(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry(self::RAW_ADVANCED_RANGE_DAMAGE_EVENT);
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_10_1_0);
        $advancedData     = $parseEventResult->getAdvancedData();

        // Act
        $rawBeforeParsing = $advancedData->getInfoGuidRaw();
        Assert::assertFalse($this->guidHasBeenParsed($advancedData, 'infoGuid'));

        $advancedData->getInfoGuid();
        Assert::assertTrue($this->guidHasBeenParsed($advancedData, 'infoGuid'));

        $rawAfterParsing = $advancedData->getInfoGuidRaw();

        // Assert
        Assert::assertEquals('Creature-0-4242-1841-14566-130909-00006285EA', $rawBeforeParsing);
        Assert::assertEquals($rawBeforeParsing, $rawAfterParsing);
    }

    /**
     * @throws \Exception
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('AdvancedData')]
    public function parseEvent_givenAV22LineWithADistinctValuePerField_returnsEachFieldFromItsOwnPosition(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry('3/25/2026 10:38:29.4491  SWING_DAMAGE,Pet-0-4241-2526-8814-165189-0203C3ACE6,"Devilsaur",0x1112,0x80000000,Creature-0-4241-2526-8814-197398-000343ACE6,"Hungry Lasher",0xa48,0x80000000,Pet-0-4241-2526-8814-165189-0203C3ACE6,Player-1303-09231FEC,291718,446020,2494,698,4052,414,77,1234,2|3,50|60,100|120,5|6,1801.07,-3077.86,2097,5.2480,247,3001,3002,-1,1,0,0,0,nil,nil,nil');

        // Act
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);
        /** @var AdvancedDataV22 $advancedData */
        $advancedData = $parseEventResult->getAdvancedData();

        // Assert
        Assert::assertSame(AdvancedDataV22::class, $advancedData::class);
        Assert::assertEquals('Pet-0-4241-2526-8814-165189-0203C3ACE6', $advancedData->getInfoGuid()?->getGuid());
        Assert::assertEquals('Player-1303-09231FEC', $advancedData->getOwnerGuid()?->getGuid());
        Assert::assertEquals(291718, $advancedData->getCurrentHP());
        Assert::assertEquals(446020, $advancedData->getMaxHP());
        Assert::assertEquals(2494, $advancedData->getAttackPower());
        Assert::assertEquals(698, $advancedData->getSpellPower());
        Assert::assertEquals(4052, $advancedData->getArmor());
        Assert::assertEquals(414, $advancedData->getUnknown1());
        Assert::assertEquals(77, $advancedData->getUnknown2());
        Assert::assertEquals(1234, $advancedData->getAbsorb());
        Assert::assertEquals([2, 3], $advancedData->getPowerType());
        Assert::assertEquals([50, 60], $advancedData->getCurrentPower());
        Assert::assertEquals([100, 120], $advancedData->getMaxPower());
        Assert::assertEquals([5, 6], $advancedData->getPowerCost());
        Assert::assertEquals(3077.86, $advancedData->getPositionX());
        Assert::assertEquals(1801.07, $advancedData->getPositionY());
        Assert::assertEquals(2097, $advancedData->getUiMapId());
        Assert::assertEquals(5.2480, $advancedData->getFacing());
        Assert::assertEquals(247, $advancedData->getLevel());
    }

    private function guidHasBeenParsed(AdvancedDataInterface $advancedData, string $property): bool
    {
        return new ReflectionProperty($advancedData, $property)->getValue($advancedData) !== false;
    }

    private function powerArrayHasBeenParsed(AdvancedDataInterface $advancedData, string $property): bool
    {
        return new ReflectionProperty($advancedData, $property)->isInitialized($advancedData);
    }

    /**
     * @return array<int, mixed>
     */
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

    /**
     * @return array<int, mixed>
     */
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
            // A distinct value in every field, so reading one field from a neighbour's position cannot pass
            [
                '5/15 21:20:24.467  SPELL_CAST_SUCCESS,Player-1084-0A6D63A6,"Sadarøn-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-131436-0000E285EA,"Chosen Blood Matron",0x10a48,0x0,22568,"Ferocious Bite",0x1,Player-1084-0A6D63A6,0000000000000000,295296,370660,8542,2087,3228,4321,3|4,47|5,100|6,25|7,685.25,1257.08,1041,5.7142,407',
                'Player-1084-0A6D63A6',
                null,
                295296,
                370660,
                8542,
                2087,
                3228,
                4321,
                [3, 4],
                [47, 5],
                [100, 6],
                [25, 7],
                -1257.08,
                685.25,
                1041,
                5.7142,
                407,
            ],
        ];
    }
}
