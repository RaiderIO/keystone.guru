<?php

namespace Database\Seeders;

use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use Illuminate\Database\Seeder;

class GameVersionsSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gameVersionAttributes = [
            [
                'expansion_id' => Expansion::ALL[Expansion::EXPANSION_TWW],
                'key'          => GameVersion::GAME_VERSION_RETAIL,
                'name'         => 'gameversions.retail.name',
                'description'  => 'gameversions.retail.description',
                'has_seasons'  => true,
                'active'       => true,
            ],
            [
                'expansion_id' => Expansion::ALL[Expansion::EXPANSION_CLASSIC],
                'key'          => GameVersion::GAME_VERSION_CLASSIC_ERA,
                'name'         => 'gameversions.classic.name',
                'description'  => 'gameversions.classic.description',
                'has_seasons'  => false,
                'active'       => true,
            ],
            [
                'expansion_id' => Expansion::ALL[Expansion::EXPANSION_WOTLK],
                'key'          => GameVersion::GAME_VERSION_WRATH,
                'name'         => 'gameversions.wotlk.name',
                'description'  => 'gameversions.wotlk.description',
                'has_seasons'  => false,
                'active'       => true,
            ],
            [
                'expansion_id' => Expansion::ALL[Expansion::EXPANSION_TWW],
                'key'          => GameVersion::GAME_VERSION_BETA,
                'name'         => 'gameversions.beta.name',
                'description'  => 'gameversions.beta.description',
                'has_seasons'  => false,
                'active'       => false,
            ],
            [
                'expansion_id' => Expansion::ALL[Expansion::EXPANSION_CATACLYSM],
                'key'          => GameVersion::GAME_VERSION_CATA,
                'name'         => 'gameversions.cata.name',
                'description'  => 'gameversions.cata.description',
                'has_seasons'  => false,
                'active'       => true,
            ],
            [
                'expansion_id' => Expansion::ALL[Expansion::EXPANSION_MOP],
                'key'          => GameVersion::GAME_VERSION_MOP,
                'name'         => 'gameversions.mop.name',
                'description'  => 'gameversions.mop.description',
                'has_seasons'  => false,
                'active'       => true,
            ],
            [
                'expansion_id' => Expansion::ALL[Expansion::EXPANSION_LEGION],
                'key'          => GameVersion::GAME_VERSION_LEGION_REMIX,
                'name'         => 'gameversions.legion-remix.name',
                'description'  => 'gameversions.legion-remix.description',
                'has_seasons'  => false,
                'active'       => true,
            ],
        ];

        GameVersion::from(DatabaseSeeder::getTempTableName(GameVersion::class))->insert($gameVersionAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [GameVersion::class];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
