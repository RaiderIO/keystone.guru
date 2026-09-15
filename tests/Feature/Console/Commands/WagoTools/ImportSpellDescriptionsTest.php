<?php

namespace Tests\Feature\Console\Commands\WagoTools;

use App\Models\GameVersion\GameVersion;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDescriptionImportState;
use App\Models\Spell\SpellEffect;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\SlowTest;
use Tests\TestCases\PublicTestCase;
use Tests\Traits\RestoresSpellDescriptionImportState;

/**
 * Runs the import end to end against DB2 CSVs placed in the download cache, so the command reads the
 * files it would have downloaded without ever reaching wago.tools.
 */
#[Group('SpellDescription')]
#[SlowTest]
final class ImportSpellDescriptionsTest extends PublicTestCase
{
    use RestoresSpellDescriptionImportState;

    private const string BUILD = '0.0.0.00000';

    private const int SPELL_ID = 999999901;

    private const int REFERENCED_SPELL_ID = 999999902;

    /** A spell id the fixture build's Spell table holds no row for at all. */
    private const int UNKNOWN_SPELL_ID = 999999903;

    private const string IMPORT_STATE_DATA_PATH = 'data/spell_description/import_state.json';

    private string $originalImportStateJson;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // A successful import rewrites this committed seeder file - restored in tearDown() below
        $this->originalImportStateJson = File::get(database_path(self::IMPORT_STATE_DATA_PATH));

        $this->captureSpellDescriptionImportState(GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        File::put(database_path(self::IMPORT_STATE_DATA_PATH), $this->originalImportStateJson);

        $this->restoreSpellDescriptionImportState();

        parent::tearDown();
    }

    #[Test]
    public function handle_givenDb2Tables_rendersTheSpellDescription(): void
    {
        // Arrange
        $spell = null;

        try {
            $this->writeDb2Tables();

            $spell = $this->createSpell(self::SPELL_ID, null);

            // Act
            $this->artisan('wagotools:importspelldescriptions', ['--build' => self::BUILD])
                ->assertSuccessful();

            // Assert
            $spell->refresh();

            $this->assertSame(
                'Slams the ground, inflicting 25 Fire damage to all players within 12 yards, and stunning them for 8 sec.',
                $spell->description,
            );
            $this->assertSame(
                'Slams the ground, inflicting $s1 Fire damage to all players within $a1 yards, and stunning them for $999999902d.',
                $spell->description_template,
            );
        } finally {
            $spell?->delete();
            new Spell()->flushCache();
            $this->clearImportState();
            $this->removeDb2Tables();
        }
    }

    #[Test]
    public function handle_givenDb2Tables_persistsEveryColumnOfTheSpellEffect(): void
    {
        // Arrange - the effect names its own columns, so this is what guards that shape against drift
        $spell = null;

        try {
            $this->writeDb2Tables();

            $spell = $this->createSpell(self::SPELL_ID, null);

            // Act
            $this->artisan('wagotools:importspelldescriptions', ['--build' => self::BUILD])
                ->assertSuccessful();

            // Assert
            $effect = SpellEffect::query()
                ->where('spell_id', self::SPELL_ID)
                ->where('effect_index', 0)
                ->first();

            $this->assertNotNull($effect);
            $this->assertSame(0, $effect->effect_type);
            $this->assertSame(0, $effect->aura_type);
            $this->assertSame(25.0, $effect->base_points);
            $this->assertSame(0.0, $effect->variance);
            $this->assertSame(0, $effect->period_ms);
            $this->assertSame(0, $effect->chain_targets);
            $this->assertSame(12.0, $effect->radius);
            $this->assertSame(12.0, $effect->max_radius);
        } finally {
            SpellEffect::query()->where('spell_id', self::SPELL_ID)->delete();
            new SpellEffect()->flushCache();
            $spell?->delete();
            new Spell()->flushCache();
            $this->clearImportState();
            $this->removeDb2Tables();
        }
    }

    #[Test]
    public function handle_givenASpellWithoutADescription_clearsTheOneWeHad(): void
    {
        // Arrange
        $describedSpell = null;
        $spell          = null;

        try {
            $this->writeDb2Tables();

            // The build must describe something, or the import refuses to run at all
            $describedSpell = $this->createSpell(self::SPELL_ID, null);
            $spell          = $this->createSpell(self::REFERENCED_SPELL_ID, 'A description of a previous game build.');

            // Act
            $this->artisan('wagotools:importspelldescriptions', ['--build' => self::BUILD])
                ->assertSuccessful();

            // Assert - the spell has no description in this build, so ours must go as well
            $spell->refresh();

            $this->assertNull($spell->description_format);
            $this->assertNull($spell->description_template);
        } finally {
            $spell?->delete();
            $describedSpell?->delete();
            new Spell()->flushCache();
            $this->clearImportState();
            $this->removeDb2Tables();
        }
    }

    #[Test]
    public function handle_givenABuildThatDescribesNothing_keepsTheDescriptionsWeHave(): void
    {
        // Arrange - a build we cannot read must never be able to wipe every description we have
        $spell = null;

        try {
            $this->writeDb2Tables(['Spell' => 'ID,NameSubtext_lang,Description_lang,AuraDescription_lang']);

            $spell = $this->createSpell(self::SPELL_ID, 'A description of a previous game build.');

            // Act
            $this->artisan('wagotools:importspelldescriptions', ['--build' => self::BUILD])
                ->assertFailed();

            // Assert
            $spell->refresh();

            $this->assertSame('A description of a previous game build.', $spell->description);
        } finally {
            $spell?->delete();
            new Spell()->flushCache();
            $this->clearImportState();
            $this->removeDb2Tables();
        }
    }

    #[Test]
    public function handle_givenASpellOfAnotherGameVersion_leavesItAlone(): void
    {
        // Arrange - a retail build has no business rewriting the spells of a classic client
        $describedSpell = null;
        $classicSpell   = null;

        try {
            $this->writeDb2Tables();

            $describedSpell = $this->createSpell(self::SPELL_ID, null);
            $classicSpell   = $this->createSpell(
                self::REFERENCED_SPELL_ID,
                'A description of the classic client.',
                GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA],
            );

            // Act
            $this->artisan('wagotools:importspelldescriptions', ['--build' => self::BUILD])
                ->assertSuccessful();

            // Assert
            $classicSpell->refresh();

            $this->assertSame('A description of the classic client.', $classicSpell->description);
        } finally {
            $classicSpell?->delete();
            $describedSpell?->delete();
            new Spell()->flushCache();
            $this->clearImportState();
            $this->removeDb2Tables();
        }
    }

    #[Test]
    public function handle_givenASpellTheBuildDoesNotHave_leavesItAlone(): void
    {
        // Arrange - a spell the client has never heard of is not a spell it dropped the description of
        $describedSpell = null;
        $unknownSpell   = null;

        try {
            $this->writeDb2Tables();

            $describedSpell = $this->createSpell(self::SPELL_ID, null);
            $unknownSpell   = $this->createSpell(self::UNKNOWN_SPELL_ID, 'A description of an older build.');

            // Act
            $this->artisan('wagotools:importspelldescriptions', ['--build' => self::BUILD])
                ->assertSuccessful();

            // Assert
            $unknownSpell->refresh();

            $this->assertSame('A description of an older build.', $unknownSpell->description);
        } finally {
            $unknownSpell?->delete();
            $describedSpell?->delete();
            new Spell()->flushCache();
            $this->clearImportState();
            $this->removeDb2Tables();
        }
    }

    private function clearImportState(): void
    {
        SpellDescriptionImportState::query()
            ->where('game_version_id', GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL])
            ->delete();
    }

    private function createSpell(int $spellId, ?string $description, ?int $gameVersionId = null): Spell
    {
        return Spell::create([
            'id'                 => $spellId,
            'game_version_id'    => $gameVersionId ?? GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            'dispel_type'        => 'spelldispeltype.none',
            'icon_name'          => 'inv_misc_questionmark',
            'name'               => 'spells.test',
            'schools_mask'       => 1,
            'description_format' => $description,
        ]);
    }

    /**
     * The rows the game client would ship for these two spells.
     *
     * @return array<string, string>
     */
    private function getDb2Tables(): array
    {
        return [
            'Spell' => <<<CSV
                ID,NameSubtext_lang,Description_lang,AuraDescription_lang
                999999901,,"Slams the ground, inflicting \$s1 Fire damage to all players within \$a1 yards, and stunning them for \$999999902d.",
                999999902,,,
                CSV,
            'SpellName' => <<<CSV
                ID,Name_lang
                999999901,"Ground Slam"
                999999902,"Dazed"
                CSV,
            'SpellEffect' => <<<CSV
                ID,DifficultyID,EffectIndex,EffectAuraPeriod,EffectChainTargets,Variance,EffectBasePointsF,EffectRadiusIndex_0,EffectRadiusIndex_1,SpellID
                1,0,0,0,0,0,25,900001,0,999999901
                CSV,
            'SpellMisc' => <<<CSV
                ID,DifficultyID,DurationIndex,SpellID
                1,0,900001,999999902
                CSV,
            'SpellDuration' => <<<CSV
                ID,Duration,MaxDuration,DurationPerResource
                900001,8000,8000,0
                CSV,
            'SpellRadius' => <<<CSV
                ID,Radius,RadiusPerLevel,RadiusMin,RadiusMax
                900001,12,0,0,12
                CSV,
            'SpellDescriptionVariables' => <<<CSV
                ID,Variables
                900001,\$mult=\${2}
                CSV,
            'SpellXDescriptionVariables' => <<<CSV
                ID,SpellID,SpellDescriptionVariablesID
                1,999999901,900001
                CSV,
        ];
    }

    /** @param array<string, string> $overrides */
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
    }

    private function getDb2Directory(): string
    {
        return storage_path(sprintf('app/db2/%s', self::BUILD));
    }
}
