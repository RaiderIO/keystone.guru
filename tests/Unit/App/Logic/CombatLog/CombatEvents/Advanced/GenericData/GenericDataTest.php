<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatEvents\Advanced\GenericData;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\GenericData\GenericDataInterface;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCases\PublicTestCase;

final class GenericDataTest extends PublicTestCase
{
    private const string RAW_ADVANCED_RANGE_DAMAGE_EVENT = '5/15 21:20:23.861  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-130909-00006285EA,"Fetid Maggot",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-130909-00006285EA,0000000000000000,980750,988005,0,0,5043,0,1,0,0,0,671.47,1235.72,1041,1.1845,70,7255,5182,-1,1,0,0,0,1,nil,nil';

    /**
     * @throws \Exception
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('GenericDataAll')]
    #[DataProvider('parseEvent_ShouldReturnAdvancedRangeDamageEvent_GivenAdvancedRangeDamageEvent_DataProvider')]
    public function parseEvent_ShouldReturnGenericData_GivenAdvancedRangeDamageEvent(
        string $advancedRangeDamageEvent,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($advancedRangeDamageEvent);

        // Act
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_10_1_0);

        // Assert
        Assert::assertInstanceOf(AdvancedCombatLogEvent::class, $combatLogEntry->getParsedEvent());
        Assert::assertInstanceOf(GenericDataInterface::class, $parseEventResult->getGenericData());
    }

    /**
     * @throws \Exception
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('GenericDataAll')]
    #[DataProvider('parseEvent_ShouldReturnValidGenericData_GivenAdvancedRangeDamageEvent_DataProvider')]
    public function parseEvent_ShouldReturnValidGenericData_GivenAdvancedRangeDamageEvent(
        string $advancedRangeDamageEvent,
        string $expectedSourceGuid,
        string $expectedSourceName,
        string $expectedSourceFlags,
        string $expectedSourceRaidFlags,
        string $expectedDestGuid,
        string $expectedDestName,
        string $expectedDestFlags,
        string $expectedDestRaidFlags,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($advancedRangeDamageEvent);

        // Act
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_10_1_0);
        $genericData      = $parseEventResult->getGenericData();

        // Assert
        Assert::assertEquals($expectedSourceGuid, $genericData->getSourceGuid());
        Assert::assertEquals($expectedSourceName, $genericData->getSourceName());
        Assert::assertEquals($expectedSourceFlags, $genericData->getSourceFlags());
        Assert::assertEquals($expectedSourceRaidFlags, $genericData->getSourceRaidFlags());
        Assert::assertEquals($expectedDestGuid, $genericData->getDestGuid());
        Assert::assertEquals($expectedDestName, $genericData->getDestName());
        Assert::assertEquals($expectedDestFlags, $genericData->getDestFlags());
        Assert::assertEquals($expectedDestRaidFlags, $genericData->getDestRaidFlags());
    }

    /**
     * @throws \Exception
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('GenericDataAll')]
    public function getGenericData_givenNoGetterCalled_neverParsesTheGuids(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry(self::RAW_ADVANCED_RANGE_DAMAGE_EVENT);

        // Act
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_10_1_0);
        $genericData      = $parseEventResult->getGenericData();

        // Assert - retrieving the interface itself must not trigger parsing of either GUID
        Assert::assertFalse($this->guidHasBeenParsed($genericData, 'sourceGuid'));
        Assert::assertFalse($this->guidHasBeenParsed($genericData, 'destGuid'));
    }

    /**
     * @throws \Exception
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('GenericDataAll')]
    public function getSourceGuid_calledTwice_parsesOnceAndReturnsTheSameInstance(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry(self::RAW_ADVANCED_RANGE_DAMAGE_EVENT);
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_10_1_0);
        $genericData      = $parseEventResult->getGenericData();

        // Act
        $first = $genericData->getSourceGuid();
        Assert::assertTrue($this->guidHasBeenParsed($genericData, 'sourceGuid'));
        $second = $genericData->getSourceGuid();

        // Assert - the second call must not re-parse, it should return the exact same instance; and
        // parsing the source GUID must not have dragged the dest GUID along with it
        Assert::assertSame($first, $second);
        Assert::assertFalse($this->guidHasBeenParsed($genericData, 'destGuid'));
    }

    /**
     * @throws \Exception
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('GenericDataAll')]
    public function getDestGuid_calledOnItsOwn_doesNotParseTheSourceGuid(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry(self::RAW_ADVANCED_RANGE_DAMAGE_EVENT);
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_10_1_0);
        $genericData      = $parseEventResult->getGenericData();

        // Act
        $destGuid = $genericData->getDestGuid();

        // Assert
        Assert::assertEquals('Creature-0-4242-1841-14566-130909-00006285EA', $destGuid?->getGuid());
        Assert::assertTrue($this->guidHasBeenParsed($genericData, 'destGuid'));
        Assert::assertFalse($this->guidHasBeenParsed($genericData, 'sourceGuid'));
    }

    /**
     * A GUID of all zeroes legitimately parses to null - the sentinel must not confuse that with
     * "not parsed yet" and re-parse it on every access.
     *
     * @throws \Exception
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('GenericDataAll')]
    public function getDestGuid_givenAnEmptyGuid_parsesToNullExactlyOnce(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry('5/15 21:20:23.861  SPELL_CAST_SUCCESS,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,0000000000000000,nil,0x80000000,0x0,75,"Auto Shot",0x1');
        /** @var \App\Logic\CombatLog\CombatEvents\CombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_10_1_0);
        $genericData      = $parseEventResult->getGenericData();

        // Act
        $destGuid = $genericData->getDestGuid();

        // Assert
        Assert::assertNull($destGuid);
        Assert::assertTrue($this->guidHasBeenParsed($genericData, 'destGuid'));
        Assert::assertNull($genericData->getDestGuid());
    }

    /**
     * @throws \Exception
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('GenericDataAll')]
    public function getSourceGuidRaw_calledBeforeAndAfterGuidParsing_returnsTheSameRawGuidString(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry(self::RAW_ADVANCED_RANGE_DAMAGE_EVENT);
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_10_1_0);
        $genericData      = $parseEventResult->getGenericData();

        // Act
        $rawBeforeParsing = $genericData->getSourceGuidRaw();
        Assert::assertFalse($this->guidHasBeenParsed($genericData, 'sourceGuid'));

        $genericData->getSourceGuid();
        Assert::assertTrue($this->guidHasBeenParsed($genericData, 'sourceGuid'));

        $rawAfterParsing = $genericData->getSourceGuidRaw();

        // Assert
        Assert::assertEquals('Player-1084-0A4BFB68', $rawBeforeParsing);
        Assert::assertEquals($rawBeforeParsing, $rawAfterParsing);
    }

    /**
     * @throws \Exception
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('GenericDataAll')]
    public function getDestGuidRaw_calledBeforeAnyParsing_returnsTheRawGuidString(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry(self::RAW_ADVANCED_RANGE_DAMAGE_EVENT);
        /** @var AdvancedCombatLogEvent $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_10_1_0);
        $genericData      = $parseEventResult->getGenericData();

        // Act
        $raw = $genericData->getDestGuidRaw();

        // Assert
        Assert::assertEquals('Creature-0-4242-1841-14566-130909-00006285EA', $raw);
        Assert::assertFalse($this->guidHasBeenParsed($genericData, 'destGuid'));
    }

    private function guidHasBeenParsed(GenericDataInterface $genericData, string $property): bool
    {
        return new ReflectionProperty($genericData, $property)->getValue($genericData) !== false;
    }

    /**
     * @return array<int, mixed>
     */
    public static function parseEvent_ShouldReturnAdvancedRangeDamageEvent_GivenAdvancedRangeDamageEvent_DataProvider(): array
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

    /**
     * @return array<int, mixed>
     */
    public static function parseEvent_ShouldReturnValidGenericData_GivenAdvancedRangeDamageEvent_DataProvider(): array
    {
        return [
            [
                '5/15 21:20:23.861  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-130909-00006285EA,"Fetid Maggot",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-130909-00006285EA,0000000000000000,980750,988005,0,0,5043,0,1,0,0,0,671.47,1235.72,1041,1.1845,70,7255,5182,-1,1,0,0,0,1,nil,nil',
                'Player-1084-0A4BFB68',
                'Ooteeny-TarrenMill',
                '0x512',
                '0x0',
                'Creature-0-4242-1841-14566-130909-00006285EA',
                'Fetid Maggot',
                '0xa48',
                '0x0',
            ], [
                '5/15 21:20:36.010  RANGE_DAMAGE,Player-1084-0A4BFB68,"Ooteeny-TarrenMill",0x512,0x0,Creature-0-4242-1841-14566-131436-0000E285EA,"Chosen Blood Matron",0xa48,0x0,75,"Auto Shot",0x1,Creature-0-4242-1841-14566-131436-0000E285EA,0000000000000000,1294007,1580808,0,0,5043,0,1,0,0,0,673.54,1254.75,1041,3.3024,71,5665,7357,-1,1,0,0,0,nil,nil,nil',
                'Player-1084-0A4BFB68',
                'Ooteeny-TarrenMill',
                '0x512',
                '0x0',
                'Creature-0-4242-1841-14566-131436-0000E285EA',
                'Chosen Blood Matron',
                '0xa48',
                '0x0',
            ],
        ];
    }
}
