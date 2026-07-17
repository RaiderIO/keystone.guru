<?php

namespace Database\Seeders;

use App\Models\MDTAddonVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

/**
 * Imports the committed database/data/mdt/addon_versions.json (the MDT addonVersion => release-date map,
 * rebuilt from GitHub by `mdt:syncaddonversions --refresh`) into the mdt_addon_versions table so the MDT
 * importer can resolve a string's addonVersion to its release date at query time (#3380).
 *
 * The table is not managed through the temp-table swap: the MDT class basename does not snake-case back to
 * `mdt_addon_versions` (it becomes `m_d_t_addon_versions`), so DatabaseSeeder::getTempTableName() cannot
 * address it. Like MappingVersionSeeder, this seeder therefore populates the real table itself — here with
 * an idempotent upsert so re-seeding simply reconciles it with the JSON.
 */
class MDTAddonVersionSeeder extends Seeder implements TableSeederInterface
{
    private const RELATIVE_DATA_PATH = 'data/mdt/addon_versions.json';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path(self::RELATIVE_DATA_PATH);
        if (!File::exists($path)) {
            $this->command->error(sprintf('MDT addon version map not found at %s', $path));

            return;
        }

        /** @var array<string, string> $decoded */
        $decoded = json_decode(File::get($path), true) ?? [];

        $rows = [];
        foreach ($decoded as $addonVersion => $publishedAt) {
            $rows[] = [
                'addon_version' => (int)$addonVersion,
                'released_at'   => Carbon::parse($publishedAt)->toDateTimeString(),
            ];
        }

        if ($rows === []) {
            $this->command->warn('MDT addon version map is empty - nothing to seed.');

            return;
        }

        MDTAddonVersion::upsert($rows, ['addon_version'], ['released_at']);

        $this->command->info(sprintf('Seeded %d MDT addon versions.', count($rows)));
    }

    public static function getAffectedModelClasses(): array
    {
        // Populates its own table directly (see class docblock), so no temp-table swap is prepared.
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
