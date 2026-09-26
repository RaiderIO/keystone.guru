<?php

namespace Tests\Feature\Service\Spell\Description;

use App\Models\GameVersion\GameVersion;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDescriptionImportState;
use App\Models\Spell\SpellDescriptionTranslation;
use App\Service\Spell\Description\SpellDescriptionImportServiceInterface;
use App\Service\WagoTools\GameLocale;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\SlowTest;
use Tests\TestCases\PublicTestCase;
use Tests\Traits\RestoresSpellDescriptionImportState;

/**
 * A spell whose description carries a coefficient (`$s1`) round-trips its `description_values` through
 * a MySQL json column, which reorders object keys on storage - the comparison in renderAndPersist()
 * must not mistake that reordering for a real change.
 */
#[Group('SpellDescription')]
#[SlowTest]
final class SpellDescriptionImportServiceTest extends PublicTestCase
{
    use RestoresSpellDescriptionImportState;

    private const int SPELL_ID = 999999911;

    /**
     * A second described spell, so that a test dropping SPELL_ID's description in one locale still
     * leaves that locale describing something - a locale that describes nothing we know is a download
     * that went wrong, and the import skips it rather than deleting on the strength of a bad file.
     */
    private const int SPELL_ID_ALWAYS_DESCRIBED = 999999912;

    /** A second spell of the test's own, standing in for the normal spell a PvP talent replaces. */
    private const int OVERRIDDEN_SPELL_ID = 999999913;

    private const string IMPORT_STATE_DATA_PATH = 'data/spell_description/import_state.json';

    private string $originalImportStateJson;

    /**
     * Own build per test run, not a shared constant - paratest splits at the test method level, so two
     * methods of this class run in different worker processes at the same time, sharing the filesystem
     * (only the database is per-worker). A fixed build id had both writing/deleting the same DB2 CSV
     * cache directory concurrently.
     */
    private string $build;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->originalImportStateJson = File::get(database_path(self::IMPORT_STATE_DATA_PATH));

        $this->captureSpellDescriptionImportState(GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]);

        $this->build = sprintf('0.0.%d.%d', getmypid(), random_int(10_000, 99_999));
    }

    #[\Override]
    protected function tearDown(): void
    {
        File::put(database_path(self::IMPORT_STATE_DATA_PATH), $this->originalImportStateJson);

        $this->restoreSpellDescriptionImportState();

        parent::tearDown();
    }

    #[Test]
    public function importDescriptions_givenASecondRunOfTheSameBuild_reportsNothingChanged(): void
    {
        // Arrange
        $spell = null;

        try {
            $this->writeDb2Tables(25);

            $spell = $this->createSpell();

            // Act
            $firstResult = $this->import();

            // Assert - the spell had no description at all yet, so the first run does change it
            $this->assertSame(1, $firstResult->updatedCount);

            $secondResult = $this->import();

            // Assert - nothing moved between the two runs; only key order in the stored json differs
            $this->assertSame(0, $secondResult->updatedCount);
        } finally {
            $this->deleteTranslations();
            $spell?->delete();
            $this->clearImportState();
            $this->removeDb2Tables();
        }
    }

    #[Test]
    public function importDescriptions_givenACoefficientThatActuallyChanged_reportsOneUpdate(): void
    {
        // Arrange
        $spell = null;

        try {
            $this->writeDb2Tables(25);

            $spell = $this->createSpell();

            $firstResult = $this->import();
            $this->assertSame(1, $firstResult->updatedCount);

            $this->writeDb2Tables(30);

            // Act
            $secondResult = $this->import();

            // Assert
            $this->assertSame(1, $secondResult->updatedCount);
        } finally {
            $this->deleteTranslations();
            $spell?->delete();
            $this->clearImportState();
            $this->removeDb2Tables();
        }
    }

    #[Test]
    public function importDescriptions_givenABuildWithTranslatedDescriptions_storesOneRowPerTranslatedLocale(): void
    {
        // Arrange
        $spell = null;

        try {
            $this->writeDb2Tables(25);

            $spell = $this->createSpell();

            // Act
            $result = $this->import();

            // Assert - English lives on the spell itself, so it gets no row of its own
            $this->assertSame(count(GameLocale::translated()), $result->translatedCount);

            $german = SpellDescriptionTranslation::query()
                ->where('spell_id', self::SPELL_ID)
                ->where('locale', GameLocale::German->value)
                ->firstOrFail();

            // The German client's own sentence, with the number lifted out of it as the English one is
            $this->assertSame('deDE inflicts %1$s Fire damage for %2$s.', $german->description_format);
            // 25 coefficient / 10 x the spell's 1.5 damage multiplier, rounded
            $this->assertSame('4', $german->description_values[0]['text']);
            // The duration unit comes from that locale's own GlobalStrings, not from an English literal
            $this->assertSame('8 sec deDE', $german->description_values[1]['text']);

            $this->assertSame(
                0,
                SpellDescriptionTranslation::query()
                    ->where('spell_id', self::SPELL_ID)
                    ->where('locale', GameLocale::English->value)
                    ->count(),
            );
        } finally {
            $this->deleteTranslations();
            $spell?->delete();
            $this->clearImportState();
            $this->removeDb2Tables();
        }
    }

    #[Test]
    public function importDescriptions_givenABuildThatDroppedALocalesDescription_removesThatLocalesRow(): void
    {
        // Arrange
        $spell      = null;
        $otherSpell = null;

        try {
            $this->writeDb2Tables(25);

            $spell      = $this->createSpell();
            $otherSpell = $this->createSpell(self::SPELL_ID_ALWAYS_DESCRIBED);
            $this->import();

            // The French client stops describing the spell, every other locale keeps doing so
            $this->writeDb2Tables(25, [GameLocale::French->value => '']);

            // Act
            $this->import();

            // Assert
            $this->assertSame(
                0,
                SpellDescriptionTranslation::query()
                    ->where('spell_id', self::SPELL_ID)
                    ->where('locale', GameLocale::French->value)
                    ->count(),
            );
            $this->assertSame(
                1,
                SpellDescriptionTranslation::query()
                    ->where('spell_id', self::SPELL_ID)
                    ->where('locale', GameLocale::German->value)
                    ->count(),
            );
        } finally {
            $this->deleteTranslations();
            $spell?->delete();
            $otherSpell?->delete();
            $this->clearImportState();
            $this->removeDb2Tables();
        }
    }

    #[Test]
    public function importDescriptions_givenAPvpTalentRow_flagsOnlyTheTalentSpell(): void
    {
        // Arrange - OverridesSpellID is the normal spell the talent replaces on the action bar, which
        // stays perfectly castable in a dungeon and must keep its flag clear
        $talentSpell     = null;
        $overriddenSpell = null;
        $flaggedBefore   = Spell::query()->where('is_pvp_talent', true)->pluck('id')->all();

        try {
            $this->writeDb2Tables(25);

            $talentSpell     = $this->createSpell();
            $overriddenSpell = $this->createSpell(self::OVERRIDDEN_SPELL_ID);

            $this->writePvpTalentTable($talentSpell->id, $overriddenSpell->id);

            // Act
            $this->import();

            // Assert
            $this->assertTrue($talentSpell->fresh()->is_pvp_talent);
            $this->assertFalse($overriddenSpell->fresh()->is_pvp_talent);
        } finally {
            $this->deleteTranslations();
            $talentSpell?->delete();
            $overriddenSpell?->delete();
            // The run clears the flag on every spell the build does not call a PvP talent, and the
            // seeded spells that carry it are shared with every other test in this schema
            Spell::query()->whereIn('id', $flaggedBefore)->update(['is_pvp_talent' => true]);
            $this->clearImportState();
            $this->removeDb2Tables();
        }
    }

    private function writePvpTalentTable(int $spellId, int $overridesSpellId): void
    {
        file_put_contents(
            sprintf('%s/PvpTalent.csv', $this->getDb2Directory()),
            sprintf("ID,SpecID,SpellID,OverridesSpellID\n1,62,%d,%d\n", $spellId, $overridesSpellId),
        );
    }

    private function import(): object
    {
        $result = app(SpellDescriptionImportServiceInterface::class)->importDescriptions(
            'wow',
            GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            $this->build,
        );

        $this->assertNotNull($result);

        return $result;
    }

    private function deleteTranslations(): void
    {
        SpellDescriptionTranslation::query()
            ->whereIn('spell_id', [self::SPELL_ID, self::SPELL_ID_ALWAYS_DESCRIBED])
            ->delete();
    }

    private function clearImportState(): void
    {
        SpellDescriptionImportState::query()
            ->where('game_version_id', GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL])
            ->delete();
    }

    private function createSpell(int $id = self::SPELL_ID): Spell
    {
        return Spell::create([
            'id'              => $id,
            'game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            'dispel_type'     => 'spelldispeltype.none',
            'icon_name'       => 'inv_misc_questionmark',
            'name'            => 'spells.test',
            'schools_mask'    => 1,
            // Only a scalable value (damage/healing) carries coefficient/spellId/effectIndex
            // (SpellDescriptionParser::appendNumber()) - those are the fields whose json-column key
            // order this test exercises, so the multiplier must be nonzero to produce them.
            'damage_multiplier' => 1.5,
        ]);
    }

    /**
     * Writes the DB2 cache for every locale the import reads, since it renders the description once per
     * locale the client publishes.
     *
     * @param array<string, string> $describedByLocale a locale's description template, for a locale that
     *                                                 should not describe the spell at all
     */
    private function writeDb2Tables(int $basePoints, array $describedByLocale = []): void
    {
        foreach (GameLocale::cases() as $locale) {
            $directory = $this->getDb2Directory($locale);

            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $description = $describedByLocale[$locale->value] ?? $this->getDescriptionTemplate($locale);

            foreach ($this->getDb2Tables($basePoints, $locale, $description) as $table => $contents) {
                // Heredocs keep the indentation of the code they sit in, which a CSV cannot have
                file_put_contents(
                    sprintf('%s/%s.csv', $directory, $table),
                    implode("\n", array_map(trim(...), explode("\n", $contents))),
                );
            }
        }
    }

    /** The template this locale's client carries for the test spell - the locale code makes it its own. */
    private function getDescriptionTemplate(GameLocale $locale): string
    {
        return sprintf('%s inflicts $s1 Fire damage for $d.', $locale->value);
    }

    /**
     * @return array<string, string>
     */
    private function getDb2Tables(int $basePoints, GameLocale $locale, string $description): array
    {
        return [
            'Spell' => <<<CSV
                ID,NameSubtext_lang,Description_lang,AuraDescription_lang
                999999911,,"{$description}",
                999999912,,"{$locale->value} always describes this one.",
                CSV,
            'SpellName' => <<<CSV
                ID,Name_lang
                999999911,"Test Blast {$locale->value}"
                999999912,"Test Blast Two {$locale->value}"
                CSV,
            'GlobalStrings' => <<<CSV
                ID,BaseTag,TagText_lang,Flags
                9868,SPELL_DURATION_SEC,"%.1f sec {$locale->value}",1
                9869,SPELL_DURATION_MIN,"%.1f min {$locale->value}",1
                CSV,
            'SpellEffect' => <<<CSV
                ID,DifficultyID,EffectIndex,Effect,EffectAuraPeriod,EffectChainTargets,Variance,EffectBasePointsF,EffectRadiusIndex_0,EffectRadiusIndex_1,SpellID
                1,0,0,2,0,0,0,{$basePoints},0,0,999999911
                CSV,
            'SpellMisc' => <<<CSV
                ID,DifficultyID,DurationIndex,SpellID
                1,0,1,999999911
                CSV,
            'SpellDuration' => <<<CSV
                ID,Duration,MaxDuration,DurationPerResource
                1,8000,8000,0
                CSV,
            'SpellRadius' => <<<CSV
                ID,Radius,RadiusPerLevel,RadiusMin,RadiusMax
                CSV,
            'SpellDescriptionVariables' => <<<CSV
                ID,Variables
                CSV,
            'SpellXDescriptionVariables' => <<<CSV
                ID,SpellID,SpellDescriptionVariablesID
                CSV,
            'PvpTalent' => <<<CSV
                ID,SpecID,SpellID,OverridesSpellID
                CSV,
        ];
    }

    private function removeDb2Tables(): void
    {
        foreach (GameLocale::cases() as $locale) {
            foreach (glob(sprintf('%s/*.csv', $this->getDb2Directory($locale))) ?: [] as $filePath) {
                unlink($filePath);
            }

            if (is_dir($this->getDb2Directory($locale))) {
                rmdir($this->getDb2Directory($locale));
            }
        }

        $buildDirectory = storage_path(sprintf('app/db2/%s', $this->build));

        if (is_dir($buildDirectory)) {
            rmdir($buildDirectory);
        }
    }

    private function getDb2Directory(GameLocale $locale = GameLocale::English): string
    {
        return storage_path(sprintf('app/db2/%s/%s', $this->build, $locale->value));
    }
}
