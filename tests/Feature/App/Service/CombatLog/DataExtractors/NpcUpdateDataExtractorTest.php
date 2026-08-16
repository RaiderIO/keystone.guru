<?php

namespace Tests\Feature\App\Service\CombatLog\DataExtractors;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Models\Dungeon;
use App\Service\CombatLog\DataExtractors\NpcUpdateDataExtractor;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('NpcUpdateDataExtractor')]
final class NpcUpdateDataExtractorTest extends PublicTestCase
{
    private const string COMBAT_LOG_PATH = '/tmp/npc-update-test.log';

    /** An advanced event whose info guid is a Creature - the shape that used to trigger a per-NPC lookup. */
    private const string RAW_ADVANCED_NPC_EVENT = '8/2/2024 16:24:18.477-4  SPELL_CAST_SUCCESS,Creature-0-4237-1209-2796-76149-0000293D52,"Dread Raven",0xa48,0x80000000,Player-1084-0A5F8492,"Jaxeek-TarrenMill-EU",0x511,0x80000000,999602,"TestSpell",0x20,Creature-0-4237-1209-2796-76149-0000293D52,0000000000000000,436040,436040,3094,437,859,100,413,35241,3,90,100,0,1238.22,1700.90,601,5.7883,287';

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
    public function extractData_givenAnAdvancedEventWithACreatureGuid_issuesNoQueries(): void
    {
        // Arrange - the extractor is a no-op placeholder until extractBaseHealth() is implemented (#4041)
        $extractor   = new NpcUpdateDataExtractor();
        $parsedEvent = $this->parsedEvent(self::RAW_ADVANCED_NPC_EVENT);

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Act - the same NPC seen repeatedly, which is what the removed per-line scan guarded against
        try {
            $extractor->beforeExtract($this->result, self::COMBAT_LOG_PATH);
            $extractor->extractData($this->result, $this->currentDungeon, $parsedEvent);
            $extractor->extractData($this->result, $this->currentDungeon, $parsedEvent);
            $extractor->afterExtract($this->result, self::COMBAT_LOG_PATH);

            $queries = DB::getRawQueryLog();
        } finally {
            DB::disableQueryLog();
        }

        // Assert
        $this->assertSame([], $queries);
    }

    private function parsedEvent(string $rawEvent): BaseEvent
    {
        return new CombatLogEntry($rawEvent)->parseEvent([], CombatLogVersion::RETAIL_11_0_5);
    }
}
