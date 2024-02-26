<?php

namespace Database\Seeders;

use App\Models\GameVersion\GameVersion;
use Illuminate\Database\Seeder;

class GameVersionsSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Adding known game versions');

        $gameVersionAttributes = [];

        foreach (GameVersion::ALL as $key => $id) {
            $gameVersionAttributes[] = [
                'id'          => $id,
                'key'         => $key,
                'name'        => sprintf('gameversions.%s.name', $key),
                'description' => sprintf('gameversions.%s.description', $key),
                'has_seasons' => $key === GameVersion::GAME_VERSION_RETAIL,
            ];
        }

        GameVersion::from(DatabaseSeeder::getTempTableName(GameVersion::class))->insert($gameVersionAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [GameVersion::class];
    }
}
