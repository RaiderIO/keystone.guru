<?php

namespace Tests\Feature\App\Service\CombatLog\DataExtractors;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedDataInterface;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Models\Dungeon;
use App\Service\CombatLog\DataExtractors\CreateMissingNpcDataExtractor;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('CreateMissingNpcDataExtractor')]
final class CreateMissingNpcDataExtractorTest extends PublicTestCase
{
    private const string COMBAT_LOG_PATH = '/tmp/create-missing-npc-test.log';

    /**
     * %s is filled in with the advanced-data info GUID under test. The rest of the event mirrors a
     * real SPELL_CAST_SUCCESS line - npc id 76149 is a pre-seeded Npc so a Creature-mapped info GUID
     * hits the "already existed" branch and issues no writes.
     */
    private const string RAW_EVENT_TEMPLATE = '8/2/2024 16:24:18.477-4  SPELL_CAST_SUCCESS,Creature-0-4237-1209-2796-76149-0000293D52,"Dread Raven",0xa48,0x80000000,Player-1084-0A5F8492,"Jaxeek-TarrenMill-EU",0x511,0x80000000,999602,"TestSpell",0x20,%s,0000000000000000,436040,436040,3094,437,859,100,413,35241,3,90,100,0,1238.22,1700.90,601,5.7883,287';

    private ExtractedDataResult $result;

    private DataExtractionCurrentDungeon $currentDungeon;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->result         = new ExtractedDataResult();
        $this->currentDungeon = new DataExtractionCurrentDungeon(Dungeon::firstOrFail());
    }

    #[Test]
    #[DataProvider('extractData_givenANonCreatureInfoGuid_neverParsesInfoGuid_DataProvider')]
    public function extractData_givenANonCreatureInfoGuid_neverParsesInfoGuid(string $infoGuid): void
    {
        // Arrange
        $extractor    = new CreateMissingNpcDataExtractor();
        $parsedEvent  = $this->parsedEvent(sprintf(self::RAW_EVENT_TEMPLATE, $infoGuid));
        $advancedData = $this->assertAdvancedEvent($parsedEvent);

        // Act
        $extractor->beforeExtract($this->result, self::COMBAT_LOG_PATH);
        $extractor->extractData($this->result, $this->currentDungeon, $parsedEvent);
        $extractor->afterExtract($this->result, self::COMBAT_LOG_PATH);

        // Assert - the raw-prefix gate must bail before touching getInfoGuid(), so the info GUID
        // is never parsed into a Guid instance
        $this->assertFalse($this->infoGuidHasBeenParsed($advancedData));
    }

    /**
     * @return array<string, mixed>
     */
    public static function extractData_givenANonCreatureInfoGuid_neverParsesInfoGuid_DataProvider(): array
    {
        return [
            'Player' => ['Player-1084-0A5F8492'],
            'nil'    => ['0000000000000000'],
        ];
    }

    #[Test]
    #[DataProvider('extractData_givenACreatureMappedInfoGuid_parsesInfoGuid_DataProvider')]
    public function extractData_givenACreatureMappedInfoGuid_parsesInfoGuid(string $infoGuid): void
    {
        // Arrange
        $extractor    = new CreateMissingNpcDataExtractor();
        $parsedEvent  = $this->parsedEvent(sprintf(self::RAW_EVENT_TEMPLATE, $infoGuid));
        $advancedData = $this->assertAdvancedEvent($parsedEvent);

        // Act
        $extractor->beforeExtract($this->result, self::COMBAT_LOG_PATH);
        $extractor->extractData($this->result, $this->currentDungeon, $parsedEvent);
        $extractor->afterExtract($this->result, self::COMBAT_LOG_PATH);

        // Assert - Creature, Pet and Vehicle GUIDs all resolve to the Creature class and must not be
        // silently skipped by a gate that only recognizes the literal "Creature-" prefix
        $this->assertTrue($this->infoGuidHasBeenParsed($advancedData));
    }

    /**
     * @return array<string, mixed>
     */
    public static function extractData_givenACreatureMappedInfoGuid_parsesInfoGuid_DataProvider(): array
    {
        return [
            'Creature' => ['Creature-0-4237-1209-2796-76149-0000293D52'],
            'Pet'      => ['Pet-0-4237-1209-2796-76149-0000293D52'],
            'Vehicle'  => ['Vehicle-0-4237-1209-2796-76149-0000293D52'],
        ];
    }

    private function assertAdvancedEvent(BaseEvent $parsedEvent): AdvancedDataInterface
    {
        $this->assertInstanceOf(AdvancedCombatLogEvent::class, $parsedEvent);

        return $parsedEvent->getAdvancedData();
    }

    private function infoGuidHasBeenParsed(AdvancedDataInterface $advancedData): bool
    {
        return new ReflectionProperty($advancedData, 'infoGuid')->getValue($advancedData) !== false;
    }

    private function parsedEvent(string $rawEvent): BaseEvent
    {
        return new CombatLogEntry($rawEvent)->parseEvent([], CombatLogVersion::RETAIL_11_0_5);
    }
}
