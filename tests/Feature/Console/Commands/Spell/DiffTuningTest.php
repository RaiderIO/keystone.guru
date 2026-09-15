<?php

namespace Tests\Feature\Console\Commands\Spell;

use App\Models\GameVersion\GameVersion;
use App\Models\Spell\SpellTuningChange;
use App\Models\Spell\SpellTuningChangeType;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Runs `spell:difftuning` against two spells.json files, so no git and no live import state is involved.
 */
#[Group('SpellTuning')]
final class DiffTuningTest extends PublicTestCase
{
    private const string FROM_BUILD = '0.0.0.00001';

    private const string TO_BUILD = '0.0.0.00002';

    private const int SPELL_ID_CHANGED = 999999811;

    private const int SPELL_ID_UNCHANGED = 999999812;

    private const int SPELL_ID_REWRITTEN = 999999813;

    private int $gameVersionId;

    private string $fromPath;

    private string $toPath;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->gameVersionId = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL)->id;

        $this->fromPath = $this->writeSpellsFile('from', [
            $this->spellEntry(self::SPELL_ID_CHANGED, 'Deals %1$s damage over %2$s.', [$this->damage('29,095', 3), $this->duration('10 sec')]),
            $this->spellEntry(self::SPELL_ID_UNCHANGED, 'Deals %1$s damage.', [$this->damage('29,095', 3)]),
            $this->spellEntry(self::SPELL_ID_REWRITTEN, 'Stuns for %1$s.', [$this->duration('3 sec')]),
        ]);
        $this->toPath = $this->writeSpellsFile('to', [
            $this->spellEntry(self::SPELL_ID_CHANGED, 'Deals %1$s damage over %2$s.', [$this->damage('38,793', 4), $this->duration('25 sec')]),
            $this->spellEntry(self::SPELL_ID_UNCHANGED, 'Deals %1$s damage.', [$this->damage('29,095', 3)]),
            $this->spellEntry(self::SPELL_ID_REWRITTEN, 'Stuns for %1$s and deals %2$s damage.', [$this->duration('3 sec'), $this->damage('9,698', 1)]),
        ]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        File::delete([$this->fromPath, $this->toPath]);
        SpellTuningChange::query()->where('to_build', self::TO_BUILD)->delete();
        new SpellTuningChange()->flushCache();

        parent::tearDown();
    }

    #[Test]
    public function handle_givenTwoFiles_storesTheChangesOfTheTargetBuild(): void
    {
        // Act
        $this->artisan('spell:difftuning', [
            '--from'       => $this->fromPath,
            '--to'         => $this->toPath,
            '--from-build' => self::FROM_BUILD,
            '--to-build'   => self::TO_BUILD,
        ])
            ->expectsOutputToContain('2 of 3 compared spells changed')
            ->assertExitCode(0);

        // Assert
        $changes = SpellTuningChange::query()->where('to_build', self::TO_BUILD)->orderBy('spell_id')->orderBy('value_index')->get();
        $this->assertCount(3, $changes);

        $this->assertSame(self::SPELL_ID_CHANGED, $changes[0]->spell_id);
        $this->assertSame(SpellTuningChangeType::ValueChanged, $changes[0]->change_type);
        $this->assertSame(self::FROM_BUILD, $changes[0]->from_build);
        $this->assertSame(2, $changes[0]->to_build_number);
        $this->assertSame(3.0, $changes[0]->old_coefficient);
        $this->assertSame(4.0, $changes[0]->new_coefficient);
        $this->assertEqualsWithDelta(1 / 3, $changes[0]->delta, 1e-9);

        $this->assertSame(self::SPELL_ID_CHANGED, $changes[1]->spell_id);
        $this->assertSame('10 sec', $changes[1]->old_text);
        $this->assertSame('25 sec', $changes[1]->new_text);
        $this->assertNull($changes[1]->delta);

        $this->assertSame(self::SPELL_ID_REWRITTEN, $changes[2]->spell_id);
        $this->assertSame(SpellTuningChangeType::DescriptionRewritten, $changes[2]->change_type);
        $this->assertSame('Stuns for 3 sec.', $changes[2]->old_text);
        $this->assertSame('Stuns for 3 sec and deals 9,698 damage.', $changes[2]->new_text);
    }

    #[Test]
    public function handle_givenSecondRunForSameBuilds_keepsTheSameRows(): void
    {
        // Arrange
        $this->runDiff();
        $firstRunIds = SpellTuningChange::query()->where('to_build', self::TO_BUILD)->pluck('id');

        // Act
        $this->runDiff();

        // Assert - rows were replaced, not appended
        $this->assertSame(3, SpellTuningChange::query()->where('to_build', self::TO_BUILD)->count());
        $this->assertCount(3, $firstRunIds);
    }

    #[Test]
    public function handle_givenEqualBuilds_returnsFailureAndStoresNothing(): void
    {
        // Act
        $this->artisan('spell:difftuning', [
            '--from'       => $this->fromPath,
            '--to'         => $this->toPath,
            '--from-build' => self::TO_BUILD,
            '--to-build'   => self::TO_BUILD,
        ])->assertExitCode(1);

        // Assert
        $this->assertSame(0, SpellTuningChange::query()->where('to_build', self::TO_BUILD)->count());
    }

    #[Test]
    public function handle_givenDryRun_storesNothing(): void
    {
        // Act
        $this->artisan('spell:difftuning', [
            '--from'       => $this->fromPath,
            '--to'         => $this->toPath,
            '--from-build' => self::FROM_BUILD,
            '--to-build'   => self::TO_BUILD,
            '--dry-run'    => true,
        ])
            ->expectsOutputToContain('2 of 3 compared spells changed')
            ->assertExitCode(0);

        // Assert
        $this->assertSame(0, SpellTuningChange::query()->where('to_build', self::TO_BUILD)->count());
    }

    #[Test]
    public function handle_givenFileWithoutBuild_returnsFailure(): void
    {
        // Act + Assert
        $this->artisan('spell:difftuning', [
            '--from'     => $this->fromPath,
            '--to'       => $this->toPath,
            '--to-build' => self::TO_BUILD,
        ])->assertExitCode(1);
    }

    private function runDiff(): void
    {
        $this->artisan('spell:difftuning', [
            '--from'       => $this->fromPath,
            '--to'         => $this->toPath,
            '--from-build' => self::FROM_BUILD,
            '--to-build'   => self::TO_BUILD,
        ])->assertExitCode(0);
    }

    /**
     * @param array<int, array<string, mixed>> $spells
     */
    private function writeSpellsFile(string $name, array $spells): string
    {
        $path = storage_path(sprintf('app/spell_tuning_difftuning_%s_%s.json', $name, uniqid()));
        File::put($path, json_encode($spells));

        return $path;
    }

    /**
     * @param  array<int, array<string, mixed>> $values
     * @return array<string, mixed>
     */
    private function spellEntry(int $id, string $format, array $values): array
    {
        return [
            'id'                 => $id,
            'game_version_id'    => $this->gameVersionId,
            'description_format' => $format,
            'description_values' => $values,
        ];
    }

    /** @return array<string, mixed> */
    private function damage(string $text, float $coefficient): array
    {
        return ['kind' => 'damage', 'text' => $text, 'coefficient' => $coefficient, 'effectIndex' => 0];
    }

    /** @return array<string, mixed> */
    private function duration(string $text): array
    {
        return ['kind' => 'duration', 'text' => $text];
    }
}
