<?php

namespace Tests\Feature\Service\Spell\Description;

use App\Models\GameVersion\GameVersion;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDescriptionImportState;
use App\Service\Spell\Description\SpellDescriptionImportServiceInterface;
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
            $spell?->delete();
            new Spell()->flushCache();
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
            $spell?->delete();
            new Spell()->flushCache();
            $this->clearImportState();
            $this->removeDb2Tables();
        }
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

    private function clearImportState(): void
    {
        SpellDescriptionImportState::query()
            ->where('game_version_id', GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL])
            ->delete();
    }

    private function createSpell(): Spell
    {
        return Spell::create([
            'id'              => self::SPELL_ID,
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

    private function writeDb2Tables(int $basePoints): void
    {
        $directory = $this->getDb2Directory();

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        foreach ($this->getDb2Tables($basePoints) as $table => $contents) {
            // Heredocs keep the indentation of the code they sit in, which a CSV cannot have
            file_put_contents(
                sprintf('%s/%s.csv', $directory, $table),
                implode("\n", array_map(trim(...), explode("\n", $contents))),
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function getDb2Tables(int $basePoints): array
    {
        return [
            'Spell' => <<<CSV
                ID,NameSubtext_lang,Description_lang,AuraDescription_lang
                999999911,,"Inflicts \$s1 Fire damage.",
                CSV,
            'SpellName' => <<<CSV
                ID,Name_lang
                999999911,"Test Blast"
                CSV,
            'SpellEffect' => <<<CSV
                ID,DifficultyID,EffectIndex,Effect,EffectAuraPeriod,EffectChainTargets,Variance,EffectBasePointsF,EffectRadiusIndex_0,EffectRadiusIndex_1,SpellID
                1,0,0,2,0,0,0,{$basePoints},0,0,999999911
                CSV,
            'SpellMisc' => <<<CSV
                ID,DifficultyID,DurationIndex,SpellID
                CSV,
            'SpellDuration' => <<<CSV
                ID,Duration,MaxDuration,DurationPerResource
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
        ];
    }

    private function removeDb2Tables(): void
    {
        foreach (glob(sprintf('%s/*.csv', $this->getDb2Directory())) ?: [] as $filePath) {
            unlink($filePath);
        }

        if (is_dir($this->getDb2Directory())) {
            rmdir($this->getDb2Directory());
        }
    }

    private function getDb2Directory(): string
    {
        return storage_path(sprintf('app/db2/%s', $this->build));
    }
}
