<?php

namespace Tests\Feature\Console\Commands\WagoTools;

use App\Models\CharacterClassSpecialization;
use App\Models\GameVersion\GameVersion;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellCategory;
use App\Models\Spell\SpellCooldownSource;
use App\Models\Spell\SpellSpecialization;
use App\Service\WagoTools\Exceptions\WagoToolsDownloadException;
use App\Service\WagoTools\GameLocale;
use App\Service\WagoTools\WagoToolsService;
use App\Service\WagoTools\WagoToolsServiceInterface;
use App\Service\Wowhead\WowheadServiceInterface;
use Generator;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesSpell;
use Tests\TestCases\PublicTestCase;

/**
 * Runs the import end to end against DB2 CSVs placed in the download cache, so the command reads the files it would
 * have downloaded without ever reaching wago.tools. Wowhead is always mocked.
 */
#[Group('SpellCooldown')]
final class ImportSpellCooldownsTest extends PublicTestCase
{
    use CreatesSpell;

    private const string BUILD = '0.0.0.05831';

    /** A talent open to the whole mage tree, with a heroic-difficulty cooldown that must not win. */
    private const int TALENT_SPELL_ID = 999999931;

    /** A frost-only talent with charges. */
    private const int CHARGES_SPELL_ID = 999999932;

    /** A fire specialization spell, which is also on the mage skill line. */
    private const int SPEC_SPELL_ID = 999999933;

    /** A talent whose group limits it to arcane and fire, and which a granted-only condition must not narrow. */
    private const int GROUP_SPELL_ID = 999999934;

    /** A baseline ability of the shaman skill line. */
    private const int CLASS_SPELL_ID = 999999935;

    /** A spell the build has no row for at all. */
    private const int UNKNOWN_SPELL_ID = 999999936;

    /** A spell the build knows, but no specialization source mentions. */
    private const int NO_SPECIALIZATION_SPELL_ID = 999999937;

    /** A talent in a tree that has loadouts of two classes, so belongs to neither. */
    private const int MIXED_TREE_SPELL_ID = 999999938;

    /** A mage spell the build has no row for, which Wowhead does know. */
    private const int WOWHEAD_SPELL_ID = 999999939;

    private const array ALL_SPELL_IDS = [
        self::TALENT_SPELL_ID,
        self::CHARGES_SPELL_ID,
        self::SPEC_SPELL_ID,
        self::GROUP_SPELL_ID,
        self::CLASS_SPELL_ID,
        self::UNKNOWN_SPELL_ID,
        self::NO_SPECIALIZATION_SPELL_ID,
        self::MIXED_TREE_SPELL_ID,
        self::WOWHEAD_SPELL_ID,
    ];

    #[\Override]
    protected function tearDown(): void
    {
        SpellSpecialization::query()->whereIn('spell_id', self::ALL_SPELL_IDS)->delete();

        $this->removeDb2Tables();

        parent::tearDown();
    }

    #[Test]
    public function handle_givenDb2Tables_storesTheBaseDifficultyCooldown(): void
    {
        // Arrange
        $this->mockWowhead();
        $this->writeDb2Tables();
        $spell = $this->createTestSpell(self::TALENT_SPELL_ID);

        // Act
        $this->artisan('wagotools:importspellcooldowns', ['--build' => self::BUILD, '--no-wowhead' => true])
            ->assertSuccessful();

        // Assert
        $spell->refresh();

        $this->assertSame(120000, $spell->cooldown_ms);
        $this->assertSame(1, $spell->charges);
        $this->assertNull($spell->charge_cooldown_ms);
        $this->assertSame(SpellCooldownSource::Db2, $spell->cooldown_source);
    }

    #[Test]
    public function handle_givenASpellWithAChargeCategory_storesItsCharges(): void
    {
        // Arrange
        $this->mockWowhead();
        $this->writeDb2Tables();
        $spell = $this->createTestSpell(self::CHARGES_SPELL_ID);

        // Act
        $this->artisan('wagotools:importspellcooldowns', ['--build' => self::BUILD, '--no-wowhead' => true])
            ->assertSuccessful();

        // Assert
        $spell->refresh();

        $this->assertSame(500, $spell->cooldown_ms);
        $this->assertSame(2, $spell->charges);
        $this->assertSame(14000, $spell->charge_cooldown_ms);
        $this->assertSame(14000, $spell->getEffectiveCooldownMs());
    }

    #[Test]
    public function handle_givenAKnownSpellWithoutACooldownRow_storesNoCooldown(): void
    {
        // Arrange
        $this->mockWowhead();
        $this->writeDb2Tables();
        $spell = $this->createTestSpell(self::SPEC_SPELL_ID, ['cooldown_ms' => 30000, 'charges' => 3]);

        // Act
        $this->artisan('wagotools:importspellcooldowns', ['--build' => self::BUILD, '--no-wowhead' => true])
            ->assertSuccessful();

        // Assert
        $spell->refresh();

        $this->assertSame(0, $spell->cooldown_ms);
        $this->assertSame(1, $spell->charges);
        $this->assertSame(SpellCooldownSource::Db2, $spell->cooldown_source);
    }

    #[Test]
    public function handle_givenASpellTheBuildDoesNotKnow_keepsWhatItHad(): void
    {
        // Arrange
        $this->mockWowhead();
        $this->writeDb2Tables();
        $knownSpell   = $this->createTestSpell(self::TALENT_SPELL_ID);
        $unknownSpell = $this->createTestSpell(self::UNKNOWN_SPELL_ID, [
            'cooldown_ms'     => 5000,
            'charges'         => 1,
            'cooldown_source' => SpellCooldownSource::Db2,
        ]);
        $fireId = $this->getOurSpecializationIds([63])[0];
        SpellSpecialization::create(['spell_id' => $unknownSpell->id, 'character_class_specialization_id' => $fireId]);

        // Act
        $this->artisan('wagotools:importspellcooldowns', ['--build' => self::BUILD, '--no-wowhead' => true])
            ->assertSuccessful();

        // Assert - the known spell proves the import ran at all
        $knownSpell->refresh();
        $unknownSpell->refresh();

        $this->assertSame(120000, $knownSpell->cooldown_ms);
        $this->assertSame(5000, $unknownSpell->cooldown_ms);
        $this->assertSame([$fireId], $this->getStoredSpecializationIds(self::UNKNOWN_SPELL_ID));
    }

    #[Test]
    public function handle_givenABuildThatKnowsNoneOfOurSpells_failsAndKeepsTheCooldowns(): void
    {
        // Arrange - a build we cannot read must never be able to wipe every cooldown we have
        $this->mockWowhead();
        $this->writeDb2Tables(['SpellName' => "ID,Name_lang\n1,\"Not one of ours\""]);
        $spell = $this->createTestSpell(self::TALENT_SPELL_ID, ['cooldown_ms' => 45000]);

        // Act
        $this->artisan('wagotools:importspellcooldowns', ['--build' => self::BUILD, '--no-wowhead' => true])
            ->assertFailed();

        // Assert
        $spell->refresh();

        $this->assertSame(45000, $spell->cooldown_ms);
    }

    #[Test]
    public function handle_givenACooldownTableWithoutTheSpellIdColumn_failsAndKeepsTheCooldowns(): void
    {
        // Arrange - a missing column must not read as "this build has no cooldowns at all"
        $this->mockWowhead();
        $this->writeDb2Tables(['SpellCooldowns' => "ID,DifficultyID,CategoryRecoveryTime,RecoveryTime\n1,0,0,120000"]);
        $spell = $this->createTestSpell(self::TALENT_SPELL_ID, ['cooldown_ms' => 45000]);

        // Act
        $this->artisan('wagotools:importspellcooldowns', ['--build' => self::BUILD, '--no-wowhead' => true])
            ->expectsOutputToContain('lacks the column(s) SpellID')
            ->assertFailed();

        // Assert
        $spell->refresh();

        $this->assertSame(45000, $spell->cooldown_ms);
    }

    #[Test]
    public function handle_givenAnEmptyCooldownTable_failsAndKeepsTheCooldowns(): void
    {
        // Arrange
        $this->mockWowhead();
        $this->writeDb2Tables(['SpellCooldowns' => 'ID,DifficultyID,CategoryRecoveryTime,RecoveryTime,StartRecoveryTime,AuraSpellID,SpellID']);
        $spell = $this->createTestSpell(self::TALENT_SPELL_ID, ['cooldown_ms' => 45000]);

        // Act
        $this->artisan('wagotools:importspellcooldowns', ['--build' => self::BUILD, '--no-wowhead' => true])
            ->expectsOutputToContain('SpellCooldowns for build ' . self::BUILD . ' has no rows')
            ->assertFailed();

        // Assert
        $spell->refresh();

        $this->assertSame(45000, $spell->cooldown_ms);
    }

    #[Test]
    public function handle_givenATalentTableWithoutAColumn_keepsTheSpecializationsButStoresTheCooldowns(): void
    {
        // Arrange
        $this->mockWowhead();
        $this->writeDb2Tables(['TraitNode' => "ID,PosX,PosY\n21,0,0"]);
        $spell    = $this->createTestSpell(self::TALENT_SPELL_ID);
        [$fireId] = $this->getOurSpecializationIds([63]);
        SpellSpecialization::create(['spell_id' => $spell->id, 'character_class_specialization_id' => $fireId]);

        // Act
        $this->artisan('wagotools:importspellcooldowns', ['--build' => self::BUILD, '--no-wowhead' => true])
            ->expectsOutputToContain('specializations were left as they were')
            ->assertSuccessful();

        // Assert
        $spell->refresh();

        $this->assertSame(120000, $spell->cooldown_ms);
        $this->assertSame([$fireId], $this->getStoredSpecializationIds(self::TALENT_SPELL_ID));
    }

    #[Test]
    public function handle_givenDb2Tables_storesEachSpellsSpecializations(): void
    {
        // Arrange
        $this->mockWowhead();
        $this->writeDb2Tables();

        foreach ([self::TALENT_SPELL_ID, self::CHARGES_SPELL_ID, self::SPEC_SPELL_ID, self::GROUP_SPELL_ID, self::CLASS_SPELL_ID] as $spellId) {
            $this->createTestSpell($spellId);
        }

        // Act
        $this->artisan('wagotools:importspellcooldowns', ['--build' => self::BUILD, '--no-wowhead' => true])
            ->assertSuccessful();

        // Assert
        $this->assertSame($this->getOurSpecializationIds([62, 63, 64]), $this->getStoredSpecializationIds(self::TALENT_SPELL_ID));
        $this->assertSame($this->getOurSpecializationIds([64]), $this->getStoredSpecializationIds(self::CHARGES_SPELL_ID));
        $this->assertSame($this->getOurSpecializationIds([63]), $this->getStoredSpecializationIds(self::SPEC_SPELL_ID));
        $this->assertSame($this->getOurSpecializationIds([62, 63]), $this->getStoredSpecializationIds(self::GROUP_SPELL_ID));
        $this->assertSame($this->getOurSpecializationIds([262, 263, 264]), $this->getStoredSpecializationIds(self::CLASS_SPELL_ID));
    }

    #[Test]
    public function handle_givenAKnownSpellNoSourcePlaces_removesTheSpecializationsItHad(): void
    {
        // Arrange
        $this->mockWowhead();
        $this->writeDb2Tables();
        $talentSpell    = $this->createTestSpell(self::TALENT_SPELL_ID);
        $unplacedSpell  = $this->createTestSpell(self::NO_SPECIALIZATION_SPELL_ID);
        $mixedTreeSpell = $this->createTestSpell(self::MIXED_TREE_SPELL_ID);
        [$elementalId]  = $this->getOurSpecializationIds([262]);
        foreach ([$talentSpell, $unplacedSpell, $mixedTreeSpell] as $spell) {
            SpellSpecialization::create(['spell_id' => $spell->id, 'character_class_specialization_id' => $elementalId]);
        }

        // Act
        $this->artisan('wagotools:importspellcooldowns', ['--build' => self::BUILD, '--no-wowhead' => true])
            ->assertSuccessful();

        // Assert
        $this->assertSame($this->getOurSpecializationIds([62, 63, 64]), $this->getStoredSpecializationIds(self::TALENT_SPELL_ID));
        $this->assertSame([], $this->getStoredSpecializationIds(self::NO_SPECIALIZATION_SPELL_ID));
        $this->assertSame([], $this->getStoredSpecializationIds(self::MIXED_TREE_SPELL_ID));
    }

    #[Test]
    public function handle_givenABuildWithoutTalentTables_keepsTheSpecializationsButStoresTheCooldowns(): void
    {
        // Arrange - the classic clients have no talent trees at all
        $this->mockWowhead();
        $this->writeDb2Tables();
        $this->app->instance(WagoToolsServiceInterface::class, $this->createWagoToolsServiceWithoutTable('TraitTreeLoadout'));
        $spell    = $this->createTestSpell(self::TALENT_SPELL_ID);
        [$fireId] = $this->getOurSpecializationIds([63]);
        SpellSpecialization::create(['spell_id' => $spell->id, 'character_class_specialization_id' => $fireId]);

        // Act
        $this->artisan('wagotools:importspellcooldowns', ['--build' => self::BUILD, '--no-wowhead' => true])
            ->expectsOutputToContain('specializations were left as they were')
            ->assertSuccessful();

        // Assert
        $spell->refresh();

        $this->assertSame(120000, $spell->cooldown_ms);
        $this->assertSame([$fireId], $this->getStoredSpecializationIds(self::TALENT_SPELL_ID));
    }

    #[Test]
    public function handle_givenAPlayerSpellTheBuildDoesNotKnow_takesItsCooldownFromWowhead(): void
    {
        // Arrange
        $this->mockWowhead([self::WOWHEAD_SPELL_ID => 'Test Spell 40 yd range Instant 1.5 min cooldown 2 Charges Requires Mage']);
        $this->writeDb2Tables();
        $knownSpell   = $this->createTestSpell(self::TALENT_SPELL_ID);
        $wowheadSpell = $this->createTestSpell(self::WOWHEAD_SPELL_ID);

        // Act
        $this->artisan('wagotools:importspellcooldowns', ['--build' => self::BUILD])
            ->assertSuccessful();

        // Assert - the spell the build knows is not Wowhead's business
        $knownSpell->refresh();
        $wowheadSpell->refresh();

        $this->assertSame(90000, $wowheadSpell->cooldown_ms);
        $this->assertSame(2, $wowheadSpell->charges);
        $this->assertSame(SpellCooldownSource::Wowhead, $wowheadSpell->cooldown_source);
        $this->assertSame(SpellCooldownSource::Db2, $knownSpell->cooldown_source);
    }

    #[Test]
    public function handle_givenNoWowheadOption_leavesTheUnknownSpellAlone(): void
    {
        // Arrange
        $this->mockWowhead([self::WOWHEAD_SPELL_ID => 'Instant 2 min cooldown']);
        $this->writeDb2Tables();
        $this->createTestSpell(self::TALENT_SPELL_ID);
        $wowheadSpell = $this->createTestSpell(self::WOWHEAD_SPELL_ID);

        // Act
        $this->artisan('wagotools:importspellcooldowns', ['--build' => self::BUILD, '--no-wowhead' => true])
            ->assertSuccessful();

        // Assert
        $wowheadSpell->refresh();

        $this->assertNull($wowheadSpell->cooldown_ms);
        $this->assertNull($wowheadSpell->cooldown_source);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createTestSpell(int $spellId, array $attributes = []): Spell
    {
        return $this->createSpell(array_merge([
            'id'              => $spellId,
            'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            'category'        => SpellCategory::Mage->translationKey(),
        ], $attributes));
    }

    /**
     * Wowhead answers with the given tooltips, and knows no other spell.
     *
     * @param array<int, string> $tooltips spell id => tooltip text
     */
    private function mockWowhead(array $tooltips = []): void
    {
        $wowheadService = $this->createMockPublic(WowheadServiceInterface::class);
        $wowheadService->method('getSpellTooltipText')
            ->willReturnCallback(static fn(int $spellId): ?string => $tooltips[$spellId] ?? null);

        $this->app->instance(WowheadServiceInterface::class, $wowheadService);
    }

    /**
     * The real service, except that one table is missing from the build as if the client never had it.
     */
    private function createWagoToolsServiceWithoutTable(string $missingTable): WagoToolsServiceInterface
    {
        $realService = $this->app->make(WagoToolsService::class);

        return new readonly class($realService, $missingTable) implements WagoToolsServiceInterface {
            public function __construct(private WagoToolsServiceInterface $realService, private string $missingTable)
            {
            }

            public function getLatestBuild(string $product): ?string
            {
                return $this->realService->getLatestBuild($product);
            }

            public function getBuildReleasedAt(string $product, string $build): ?Carbon
            {
                return $this->realService->getBuildReleasedAt($product, $build);
            }

            public function getTableCsvPath(string $table, string $build, GameLocale $locale = GameLocale::English): string
            {
                return $this->realService->getTableCsvPath($table, $build, $locale);
            }

            public function readTable(string $table, string $build, GameLocale $locale = GameLocale::English): Generator
            {
                if ($table === $this->missingTable) {
                    throw new WagoToolsDownloadException(sprintf('Unable to download DB2 table %s for build %s', $table, $build));
                }

                return $this->realService->readTable($table, $build, $locale);
            }

            public function getIconFileNamesByFileDataIds(array $fileDataIds, string $build): array
            {
                return $this->realService->getIconFileNamesByFileDataIds($fileDataIds, $build);
            }
        };
    }

    /**
     * @param  list<int> $blizzardSpecializationIds
     * @return list<int> character_class_specializations.id, ascending
     */
    private function getOurSpecializationIds(array $blizzardSpecializationIds): array
    {
        $ids = CharacterClassSpecialization::query()
            ->whereIn('specialization_id', $blizzardSpecializationIds)
            ->pluck('id')
            ->map(static fn($id) => (int)$id)
            ->sort()
            ->values()
            ->all();

        $this->assertCount(count($blizzardSpecializationIds), $ids, 'The seed lacks a specialization the fixture needs');

        return $ids;
    }

    /**
     * @return list<int>
     */
    private function getStoredSpecializationIds(int $spellId): array
    {
        return SpellSpecialization::query()
            ->where('spell_id', $spellId)
            ->orderBy('character_class_specialization_id')
            ->pluck('character_class_specialization_id')
            ->map(static fn($id) => (int)$id)
            ->all();
    }

    /**
     * The rows the game client would ship for the fixture spells. Mage is class 8 with specializations 62-64 and an
     * initial specialization 1449 we do not track; shaman is class 7 with 262-264.
     *
     * @return array<string, string>
     */
    private function getDb2Tables(): array
    {
        return [
            'SpellName' => <<<CSV
                ID,Name_lang
                999999931,"Talent"
                999999932,"Charged"
                999999933,"Spec Spell"
                999999934,"Grouped"
                999999935,"Class Spell"
                999999937,"Unplaced"
                999999938,"Mixed Tree"
                CSV,
            'SpellCooldowns' => <<<CSV
                ID,DifficultyID,CategoryRecoveryTime,RecoveryTime,StartRecoveryTime,AuraSpellID,SpellID
                1,0,0,120000,1500,0,999999931
                2,8,0,60000,0,0,999999931
                3,0,0,500,1500,0,999999932
                CSV,
            'SpellCategories' => <<<CSV
                ID,DifficultyID,Category,DefenseType,DiminishType,DispelType,Mechanic,PreventionType,StartRecoveryCategory,ChargeCategory,SpellID
                1,0,0,0,0,0,0,0,0,900001,999999932
                CSV,
            'SpellCategory' => <<<CSV
                ID,Name_lang,Flags,UsesPerWeek,MaxCharges,ChargeRecoveryTime,TypeMask
                900001,"Charged",0,0,2,14000,0
                CSV,
            'ChrSpecialization' => <<<CSV
                Name_lang,FemaleName_lang,Description_lang,ID,ClassID
                "Arcane",,,62,8
                "Fire",,,63,8
                "Frost",,,64,8
                "Initial",,,1449,8
                "Elemental",,,262,7
                "Enhancement",,,263,7
                "Restoration",,,264,7
                CSV,
            'SpecializationSpells' => <<<CSV
                Description_lang,ID,SpecID,SpellID,OverridesSpellID,DisplayOrder
                ,1,63,999999933,0,0
                CSV,
            'TraitTreeLoadout' => <<<CSV
                ID,TraitTreeID,ChrSpecializationID
                1,900,62
                2,900,63
                3,900,0
                4,901,262
                5,901,263
                6,902,64
                7,902,264
                CSV,
            'SkillLineXTraitTree' => <<<CSV
                ID,SkillLineID,TraitTreeID,Variant
                1,904,900,0
                2,924,901,0
                CSV,
            'SkillLineAbility' => <<<CSV
                AbilityVerb_lang,AbilityAllVerb_lang,ID,SkillLine,Spell,MinSkillLineRank,ClassMask,SupercedesSpell,AcquireMethod
                ,,1,904,999999933,1,128,0,2
                ,,2,924,999999935,1,64,0,2
                ,,3,1,999999937,1,0,0,0
                CSV,
            'TraitDefinition' => <<<CSV
                OverrideName_lang,OverrideSubtext_lang,OverrideDescription_lang,ID,SpellID,OverrideIcon,OverridesSpellID,VisibleSpellID
                ,,,1,999999931,0,0,0
                ,,,2,999999932,0,0,0
                ,,,3,999999934,0,0,0
                ,,,4,999999938,0,0,0
                CSV,
            'TraitNodeEntry' => <<<CSV
                ID,TraitDefinitionID,MaxRanks,NodeEntryType,TraitSubTreeID
                11,1,1,1,0
                12,2,1,1,0
                13,3,1,1,0
                14,4,1,1,0
                CSV,
            'TraitNodeXTraitNodeEntry' => <<<CSV
                ID,TraitNodeID,TraitNodeEntryID,_Index
                1,21,11,0
                2,22,12,0
                3,23,13,0
                4,24,14,0
                CSV,
            'TraitNode' => <<<CSV
                ID,TraitTreeID,PosX,PosY,Type,Flags,TraitSubTreeID
                21,900,0,0,0,0,0
                22,900,0,0,0,0,0
                23,900,0,0,0,0,0
                24,902,0,0,0,0,0
                CSV,
            'SpecSetMember' => <<<CSV
                ID,ChrSpecializationID,SpecSet
                1,64,50
                2,62,51
                3,63,51
                4,64,52
                CSV,
            'TraitCond' => <<<CSV
                ID,CondType,TraitTreeID,GrantedRanks,QuestID,AchievementID,SpecSetID
                31,1,900,0,0,0,50
                32,0,900,0,0,0,51
                33,2,900,1,0,0,52
                CSV,
            'TraitNodeXTraitCond' => <<<CSV
                ID,TraitCondID,TraitNodeID
                1,31,22
                2,33,23
                CSV,
            'TraitNodeGroupXTraitCond' => <<<CSV
                ID,TraitCondID,TraitNodeGroupID
                1,32,77
                CSV,
            'TraitNodeGroupXTraitNode' => <<<CSV
                ID,TraitNodeGroupID,TraitNodeID,_Index
                1,77,23,0
                2,78,21,0
                CSV,
        ];
    }

    /**
     * @param array<string, string> $overrides table => CSV contents replacing the fixture's
     */
    private function writeDb2Tables(array $overrides = []): void
    {
        $directory = $this->getDb2Directory();

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        foreach ($overrides + $this->getDb2Tables() as $table => $contents) {
            // Heredocs keep the indentation of the code they sit in, which a CSV cannot have
            file_put_contents(
                sprintf('%s/%s.csv', $directory, $table),
                implode("\n", array_map(trim(...), explode("\n", $contents))),
            );
        }
    }

    private function removeDb2Tables(): void
    {
        foreach (glob(sprintf('%s/*.csv', $this->getDb2Directory())) ?: [] as $filePath) {
            unlink($filePath);
        }

        if (is_dir($this->getDb2Directory())) {
            rmdir($this->getDb2Directory());
        }

        $buildDirectory = storage_path(sprintf('app/db2/%s', self::BUILD));

        if (is_dir($buildDirectory)) {
            rmdir($buildDirectory);
        }
    }

    private function getDb2Directory(): string
    {
        return storage_path(sprintf('app/db2/%s/%s', self::BUILD, GameLocale::English->value));
    }
}
