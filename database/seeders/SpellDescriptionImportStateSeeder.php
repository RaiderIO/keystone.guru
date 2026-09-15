<?php

namespace Database\Seeders;

use App\Models\Spell\SpellDescriptionImportState;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

/**
 * Imports the committed database/data/spell_description/import_state.json (the build
 * `wagotools:importspelldescriptions` last actually used, per game version - rewritten by that command on
 * every successful import) into the spell_description_import_states table, so the build the scheduled
 * patch check (#4021) compares against travels with the release instead of staying empty on every
 * environment that never runs the import itself.
 */
class SpellDescriptionImportStateSeeder extends Seeder implements TableSeederInterface
{
    private const RELATIVE_DATA_PATH = 'data/spell_description/import_state.json';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path(self::RELATIVE_DATA_PATH);
        if (!File::exists($path)) {
            $this->command->warn('Spell description import state file not found - nothing to seed.');

            return;
        }

        /** @var array<string, array{product: string, build: string, importedAt: string}> $decoded */
        $decoded = json_decode(File::get($path), true) ?? [];

        $rows = [];
        foreach ($decoded as $gameVersionId => $state) {
            $rows[] = [
                'game_version_id' => (int)$gameVersionId,
                'product'         => $state['product'],
                'build'           => $state['build'],
                'imported_at'     => Carbon::parse($state['importedAt'])->toDateTimeString(),
            ];
        }

        if ($rows === []) {
            $this->command->warn('Spell description import state file is empty - nothing to seed.');

            return;
        }

        SpellDescriptionImportState::query()->upsert($rows, ['game_version_id'], ['product', 'build', 'imported_at']);
    }

    public static function getAffectedModelClasses(): array
    {
        // Populates its own table directly (like MDTAddonVersionSeeder), so no temp-table swap is prepared.
        return [];
    }

    /**
     * @return array<int, string>|null
     */
    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
