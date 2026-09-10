<?php

namespace Tests\Feature\App\Service\Spell\Tuning;

use App\Models\GameVersion\GameVersion;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDescriptionImportState;
use App\Service\Spell\Tuning\Exceptions\SpellTuningSnapshotException;
use App\Service\Spell\Tuning\SpellTuningSnapshotLoaderInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;
use Tests\Traits\RestoresSpellDescriptionImportState;

#[Group('SpellTuning')]
final class SpellTuningSnapshotLoaderTest extends PublicTestCase
{
    use RestoresSpellDescriptionImportState;

    private const string BUILD = '0.0.0.00042';

    private SpellTuningSnapshotLoaderInterface $loader;

    private int $gameVersionId;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->loader        = app(SpellTuningSnapshotLoaderInterface::class);
        $this->gameVersionId = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL)->id;

        $this->captureSpellDescriptionImportState($this->gameVersionId);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->restoreSpellDescriptionImportState();

        parent::tearDown();
    }

    #[Test]
    public function load_givenFilePathWithoutBuild_throwsSpellTuningSnapshotException(): void
    {
        // Arrange
        $path = $this->writeSpellsFile([$this->spellEntry(999999801)]);

        try {
            // Assert
            $this->expectException(SpellTuningSnapshotException::class);

            // Act
            $this->loader->load($path, null, $this->gameVersionId);
        } finally {
            File::delete($path);
        }
    }

    #[Test]
    public function load_givenFilePathWithBuild_returnsSnapshotOfThatGameVersion(): void
    {
        // Arrange
        $path = $this->writeSpellsFile([
            $this->spellEntry(999999801),
            $this->spellEntry(999999802, gameVersionId: $this->gameVersionId + 1),
        ]);

        try {
            // Act
            $snapshot = $this->loader->load($path, self::BUILD, $this->gameVersionId);

            // Assert
            $this->assertSame(self::BUILD, $snapshot->build);
            $this->assertSame($this->gameVersionId, $snapshot->gameVersionId);
            $this->assertSame([999999801], array_keys($snapshot->spells));
            $this->assertSame('Deals %1$s damage.', $snapshot->spells[999999801]->descriptionFormat);
            $this->assertSame(3.0, $snapshot->spells[999999801]->values[0]->coefficient);
            $this->assertSame('inv_sword_04', $snapshot->spells[999999801]->iconName);
            $this->assertSame('spelldispeltype.n_a', $snapshot->spells[999999801]->dispelType);
        } finally {
            File::delete($path);
        }
    }

    #[Test]
    public function load_givenDatabaseSource_usesRecordedBuildAndLiveSpells(): void
    {
        // Arrange
        $this->recordImportState(self::BUILD);

        // Act
        $snapshot = $this->loader->load(SpellTuningSnapshotLoaderInterface::SOURCE_DATABASE, null, $this->gameVersionId);

        // Assert
        $this->assertSame(self::BUILD, $snapshot->build);
        $this->assertSame(Spell::query()->where('game_version_id', $this->gameVersionId)->count(), count($snapshot->spells));
    }

    #[Test]
    public function load_givenDatabaseSourceWithOverride_prefersOverride(): void
    {
        // Act
        $snapshot = $this->loader->load(SpellTuningSnapshotLoaderInterface::SOURCE_DATABASE, self::BUILD, $this->gameVersionId);

        // Assert
        $this->assertSame(self::BUILD, $snapshot->build);
    }

    #[Test]
    public function load_givenDatabaseSource_populatesIconNameAndDispelTypeFromSpells(): void
    {
        // Arrange
        $this->recordImportState(self::BUILD);
        $spell = Spell::query()->where('game_version_id', $this->gameVersionId)->firstOrFail();

        // Act
        $snapshot = $this->loader->load(SpellTuningSnapshotLoaderInterface::SOURCE_DATABASE, null, $this->gameVersionId);

        // Assert
        $this->assertSame($spell->icon_name, $snapshot->spells[$spell->id]->iconName);
        $this->assertSame($spell->dispel_type, $snapshot->spells[$spell->id]->dispelType);
    }

    #[Test]
    public function load_givenGitRef_readsSpellsAndBuildFromThatCommit(): void
    {
        // Arrange
        Process::fake([
            '*spells.json*'       => Process::result(json_encode([$this->spellEntry(999999801)])),
            '*import_state.json*' => Process::result(json_encode([(string)$this->gameVersionId => ['product' => 'wow', 'build' => self::BUILD]])),
        ]);

        // Act
        $snapshot = $this->loader->load('abcdef0', null, $this->gameVersionId);

        // Assert
        $this->assertSame(self::BUILD, $snapshot->build);
        $this->assertSame([999999801], array_keys($snapshot->spells));
        Process::assertRan(static fn($process): bool => str_contains(implode(' ', (array)$process->command), 'abcdef0:database/seeders/dungeondata/spells.json'));
    }

    #[Test]
    public function load_givenGitRefWithoutImportState_throwsUnlessBuildGiven(): void
    {
        // Arrange - the ref predates import_state.json: git show fails for it
        Process::fake([
            '*spells.json*'       => Process::result(json_encode([$this->spellEntry(999999801)])),
            '*import_state.json*' => Process::result(output: '', errorOutput: 'fatal: path does not exist', exitCode: 128),
        ]);

        // Act
        $snapshot = $this->loader->load('abcdef0', self::BUILD, $this->gameVersionId);

        // Assert
        $this->assertSame(self::BUILD, $snapshot->build);

        // Assert - and without the override it is an error
        $this->expectException(SpellTuningSnapshotException::class);
        $this->loader->load('abcdef0', null, $this->gameVersionId);
    }

    #[Test]
    public function load_givenUnknownRef_throwsSpellTuningSnapshotException(): void
    {
        // Arrange
        Process::fake([
            '*' => Process::result(output: '', errorOutput: 'fatal: invalid object name', exitCode: 128),
        ]);

        // Assert
        $this->expectException(SpellTuningSnapshotException::class);

        // Act
        $this->loader->load('not-a-ref-nor-a-file', null, $this->gameVersionId);
    }

    private function recordImportState(string $build): void
    {
        SpellDescriptionImportState::query()->updateOrCreate(
            ['game_version_id' => $this->gameVersionId],
            ['product' => 'wow', 'build' => $build, 'imported_at' => now()],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $spells
     */
    private function writeSpellsFile(array $spells): string
    {
        $path = storage_path(sprintf('app/spell_tuning_test_%s.json', uniqid()));
        File::put($path, json_encode($spells));

        return $path;
    }

    /** @return array<string, mixed> */
    private function spellEntry(int $id, ?int $gameVersionId = null): array
    {
        return [
            'id'                 => $id,
            'game_version_id'    => $gameVersionId ?? $this->gameVersionId,
            'description_format' => 'Deals %1$s damage.',
            'description_values' => [['kind' => 'damage', 'text' => '29,095', 'spellId' => $id, 'coefficient' => 3, 'effectIndex' => 0]],
            'icon_name'          => 'inv_sword_04',
            'dispel_type'        => 'spelldispeltype.n_a',
        ];
    }
}
