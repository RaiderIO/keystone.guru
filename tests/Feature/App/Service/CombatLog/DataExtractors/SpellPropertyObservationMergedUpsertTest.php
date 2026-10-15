<?php

namespace Tests\Feature\App\Service\CombatLog\DataExtractors;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\CombatLog\SpellProperty;
use App\Models\Dungeon;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\KnownSpell;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use App\Repositories\Swoole\SpellRepositorySwoole;
use App\Service\CombatLog\DataExtractors\DataExtractorFactory;
use App\Service\CombatLog\DataExtractors\DataExtractorInterface;
use App\Service\CombatLog\DataExtractors\SpellCounters\VanishSpellCounterDefinition;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Runs the factory's full extractor set over one combat log in which SpellDataExtractor, SpellCounterDataExtractor
 * and ImmunityBypassDataExtractor each observe a property of the same spell.
 */
#[Group('CombatLog')]
#[Group('SpellPropertyObservationMergedUpsert')]
final class SpellPropertyObservationMergedUpsertTest extends PublicTestCase
{
    private const string COMBAT_LOG_PATH = '/tmp/merged-observation-upsert-test.log';

    private const string PREVIOUS_COMBAT_LOG_PATH = '/tmp/merged-observation-upsert-test-previous.log';

    private const string PLAYER_GUID         = 'Player-1084-0A5F8492';
    private const string CREATURE_GUID       = 'Creature-0-4237-1209-2796-76149-0000293D52';
    private const string OTHER_CREATURE_GUID = 'Creature-0-4237-1209-2796-76149-0000293D53';

    private const int SPELL_ID        = 9992001;
    private const int DEBUFF_SPELL_ID = 9992002;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanUpTestData();
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            $this->cleanUpTestData();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function afterExtract_givenPropertiesObservedByAllThreeWriters_writesEveryColumnOfEveryRowInOneUpsert(): void
    {
        // Arrange - an observation of today's key from an earlier combat log already exists
        $this->createTestSpell(self::SPELL_ID);
        $this->createTestSpell(self::DEBUFF_SPELL_ID, 12000);
        $earlierTimestamp = Carbon::now()->subHours(2)->startOfSecond();
        $existing         = CombatLogSpellPropertyObservation::query()->forceCreate([
            'spell_id'        => self::SPELL_ID,
            'property'        => SpellProperty::CounterVanish,
            'observed_on'     => Carbon::today(),
            'combat_log_path' => self::PREVIOUS_COMBAT_LOG_PATH,
            'created_at'      => $earlierTimestamp,
            'updated_at'      => $earlierTimestamp,
        ]);
        $extractors           = new DataExtractorFactory(new SpellRepositorySwoole())->createExtractors();
        $observationUpserts   = 0;
        $propertyWritesBefore = 0;
        DB::listen(function (QueryExecuted $query) use (&$observationUpserts, &$propertyWritesBefore): void {
            if (str_starts_with($query->sql, 'insert into `combat_log_spell_property_observations`')) {
                $observationUpserts++;
            } elseif ($observationUpserts === 0 &&
                (str_starts_with($query->sql, 'update `spells` set') || str_starts_with($query->sql, 'insert into `combat_log_spell_events`'))) {
                $propertyWritesBefore++;
            }
        });

        // Act
        $this->runExtract($extractors, [
            // SpellDataExtractor: an NPC buffing another NPC is an aura
            $this->parse(sprintf(
                '%s  SPELL_AURA_APPLIED,%s,%s,%d,"Lens Flare",0x1,BUFF',
                $this->timestamp(0),
                $this->actorFields(self::CREATURE_GUID),
                $this->actorFields(self::OTHER_CREATURE_GUID),
                self::SPELL_ID,
            )),
            // SpellCounterDataExtractor: the cast's targeting debuff drops at Vanish
            $this->parse(sprintf('%s  SPELL_CAST_START,%s,%s,%d,"Lens Flare",0x8', $this->timestamp(1000), $this->actorFields(self::CREATURE_GUID), $this->actorFields(null), self::SPELL_ID)),
            $this->parse(sprintf('%s  SPELL_AURA_APPLIED,%s,%s,%d,"Lens Flare",0x4,DEBUFF', $this->timestamp(1000), $this->actorFields(null), $this->actorFields(self::PLAYER_GUID), self::DEBUFF_SPELL_ID)),
            $this->parse(sprintf('%s  SPELL_AURA_REMOVED,%s,%s,%d,"Lens Flare",0x4,DEBUFF', $this->timestamp(2999), $this->actorFields(null), $this->actorFields(self::PLAYER_GUID), self::DEBUFF_SPELL_ID)),
            $this->parse(sprintf(
                '%s  SPELL_CAST_SUCCESS,%s,%s,%d,"Vanish",0x1,%s',
                $this->timestamp(3000),
                $this->actorFields(self::PLAYER_GUID),
                $this->actorFields(null),
                VanishSpellCounterDefinition::SPELL_ID_VANISH_CAST,
                $this->advancedFields(self::PLAYER_GUID),
            )),
            // ImmunityBypassDataExtractor: the NPC's spell damages a player inside Divine Shield
            $this->parse(sprintf('%s  SPELL_AURA_APPLIED,%s,%s,%d,"Divine Shield",0x2,BUFF', $this->timestamp(10000), $this->actorFields(self::PLAYER_GUID), $this->actorFields(self::PLAYER_GUID), KnownSpell::DivineShield->value)),
            $this->parse(sprintf(
                '%s  SPELL_DAMAGE,%s,%s,%d,"Lens Flare",0x20,%s,12345,12345,0,32,0,0,0,nil,nil,nil,ST',
                $this->timestamp(12000),
                $this->actorFields(self::CREATURE_GUID),
                $this->actorFields(self::PLAYER_GUID),
                self::SPELL_ID,
                $this->advancedFields(self::CREATURE_GUID),
            )),
            $this->parse(sprintf('%s  SPELL_AURA_REMOVED,%s,%s,%d,"Divine Shield",0x2,BUFF', $this->timestamp(18000), $this->actorFields(self::PLAYER_GUID), $this->actorFields(self::PLAYER_GUID), KnownSpell::DivineShield->value)),
        ]);

        // Assert - a property written before its observation could be cleared by the staleness sweep in between
        $this->assertSame(1, $observationUpserts);
        $this->assertSame(0, $propertyWritesBefore);
        $this->assertSame(3, CombatLogSpellEvent::query()->where('spell_id', self::SPELL_ID)->count());

        /** @var Collection<string, CombatLogSpellPropertyObservation> $observations */
        $observations = CombatLogSpellPropertyObservation::query()
            ->where('spell_id', self::SPELL_ID)
            ->get()
            ->keyBy(static fn(CombatLogSpellPropertyObservation $observation) => $observation->property->value);
        $this->assertEqualsCanonicalizing(
            [SpellProperty::Aura->value, SpellProperty::CounterVanish->value, SpellProperty::BypassDivineShield->value],
            $observations->keys()->all(),
        );

        foreach ($observations as $observation) {
            $this->assertSame(Carbon::today()->toDateString(), $observation->observed_on->toDateString());
            $this->assertSame(self::COMBAT_LOG_PATH, $observation->combat_log_path);
            $this->assertTrue($observation->updated_at->greaterThan($earlierTimestamp));
        }

        $this->assertSame($existing->id, $observations->get(SpellProperty::CounterVanish->value)->id);
        $this->assertTrue($earlierTimestamp->equalTo($observations->get(SpellProperty::CounterVanish->value)->created_at));
        $this->assertTrue($observations->get(SpellProperty::Aura->value)->created_at->greaterThan($earlierTimestamp));
        $this->assertTrue($observations->get(SpellProperty::BypassDivineShield->value)->created_at->greaterThan($earlierTimestamp));
    }

    /**
     * @param Collection<int, DataExtractorInterface> $extractors
     * @param list<BaseEvent>                         $events
     */
    private function runExtract(Collection $extractors, array $events): void
    {
        $result         = new ExtractedDataResult();
        $currentDungeon = new DataExtractionCurrentDungeon(Dungeon::first());

        foreach ($extractors as $extractor) {
            $extractor->beforeExtract($result, self::COMBAT_LOG_PATH);
        }

        foreach ($events as $event) {
            foreach ($extractors as $extractor) {
                $extractor->extractData($result, $currentDungeon, $event);
            }
        }

        foreach ($extractors as $extractor) {
            $extractor->afterExtract($result, self::COMBAT_LOG_PATH);
        }
    }

    private function createTestSpell(int $spellId, ?int $duration = null): Spell
    {
        return Spell::create([
            'id'              => $spellId,
            'game_version_id' => 1,
            'category'        => null,
            'dispel_type'     => 'none',
            'icon_name'       => 'inv_misc_questionmark',
            'name'            => sprintf('Test Spell %d', $spellId),
            'schools_mask'    => 1,
            'duration'        => $duration,
        ]);
    }

    private function cleanUpTestData(): void
    {
        $spellIds = [self::SPELL_ID, self::DEBUFF_SPELL_ID];

        CombatLogSpellPropertyObservation::query()->whereIn('spell_id', $spellIds)->delete();
        CombatLogSpellEvent::query()->whereIn('spell_id', $spellIds)->delete();
        CombatLogNpcEvent::query()->where('model_class', Spell::class)->whereIn('model_id', $spellIds)->delete();
        NpcSpell::query()->whereIn('spell_id', $spellIds)->delete();
        SpellDungeon::query()->whereIn('spell_id', $spellIds)->delete();
        Spell::query()->whereIn('id', $spellIds)->delete();
    }

    private function timestamp(int $offsetMs): string
    {
        return sprintf('%s-4', Carbon::create(2024, 8, 2, 16, 24, 18)->addMilliseconds($offsetMs)->format('n/j/Y H:i:s.v'));
    }

    private function actorFields(?string $guid): string
    {
        if ($guid === null) {
            return '0000000000000000,nil,0x80000000,0x80000000';
        }

        if (str_starts_with($guid, 'Player-')) {
            return sprintf('%s,"Jaxeek-TarrenMill-EU",0x511,0x80000000', $guid);
        }

        return sprintf('%s,"Dread Raven",0xa48,0x80000000', $guid);
    }

    private function advancedFields(string $infoGuid): string
    {
        return sprintf(
            '%s,0000000000000000,436040,436040,3094,437,859,100,413,35241,3,90,100,0,1238.22,1700.90,601,5.7883,287',
            $infoGuid,
        );
    }

    private function parse(string $rawEvent): BaseEvent
    {
        return new CombatLogEntry($rawEvent)->parseEvent([], CombatLogVersion::RETAIL_11_0_5);
    }
}
