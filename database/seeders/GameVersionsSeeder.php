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
                'expansion_id'                 => Expansion::ALL[Expansion::EXPANSION_MIDNIGHT],
                'parent_game_version_id'       => null,
                'key'                          => GameVersion::GAME_VERSION_RETAIL,
                'display_order'                => 1,
                'name'                         => 'gameversions.retail.name',
                'description'                  => 'gameversions.retail.description',
                'has_seasons'                  => true,
                'has_compendium'               => true,
                'active'                       => true,
                'retired_into_game_version_id' => null,
            ],
            [
                'expansion_id'                 => Expansion::ALL[Expansion::EXPANSION_CLASSIC],
                'parent_game_version_id'       => null,
                'key'                          => GameVersion::GAME_VERSION_CLASSIC_ERA,
                'display_order'                => 2,
                'name'                         => 'gameversions.classic.name',
                'description'                  => 'gameversions.classic.description',
                'has_seasons'                  => false,
                'has_compendium'               => false,
                'active'                       => true,
                'retired_into_game_version_id' => null,
            ],
            [
                'expansion_id'                 => Expansion::ALL[Expansion::EXPANSION_WOTLK],
                'parent_game_version_id'       => null,
                'key'                          => GameVersion::GAME_VERSION_WRATH,
                'display_order'                => 7,
                'name'                         => 'gameversions.wotlk.name',
                'description'                  => 'gameversions.wotlk.description',
                'has_seasons'                  => false,
                'has_compendium'               => false,
                'active'                       => false,
                'retired_into_game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            ],
            [
                'expansion_id'                 => Expansion::ALL[Expansion::EXPANSION_TWW],
                'parent_game_version_id'       => null,
                'key'                          => GameVersion::GAME_VERSION_BETA,
                'display_order'                => 10,
                'name'                         => 'gameversions.beta.name',
                'description'                  => 'gameversions.beta.description',
                'has_seasons'                  => false,
                'has_compendium'               => false,
                'active'                       => false,
                'retired_into_game_version_id' => null,
            ],
            [
                'expansion_id'                 => Expansion::ALL[Expansion::EXPANSION_CATACLYSM],
                'parent_game_version_id'       => null,
                'key'                          => GameVersion::GAME_VERSION_CATA,
                'display_order'                => 8,
                'name'                         => 'gameversions.cata.name',
                'description'                  => 'gameversions.cata.description',
                'has_seasons'                  => false,
                'has_compendium'               => false,
                'active'                       => false,
                'retired_into_game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            ],
            [
                'expansion_id'                 => Expansion::ALL[Expansion::EXPANSION_MOP],
                'parent_game_version_id'       => null,
                'key'                          => GameVersion::GAME_VERSION_MOP,
                'display_order'                => 5,
                'name'                         => 'gameversions.mop.name',
                'description'                  => 'gameversions.mop.description',
                'has_seasons'                  => false,
                'has_compendium'               => false,
                'active'                       => true,
                'retired_into_game_version_id' => null,
            ],
            [
                'expansion_id'                 => Expansion::ALL[Expansion::EXPANSION_LEGION],
                'parent_game_version_id'       => null,
                'key'                          => GameVersion::GAME_VERSION_LEGION_REMIX,
                'display_order'                => 9,
                'name'                         => 'gameversions.legion-remix.name',
                'description'                  => 'gameversions.legion-remix.description',
                'has_seasons'                  => false,
                'has_compendium'               => false,
                'active'                       => false,
                'retired_into_game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            ],
            [
                'expansion_id'                 => Expansion::ALL[Expansion::EXPANSION_CLASSIC],
                'parent_game_version_id'       => GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA],
                'key'                          => GameVersion::GAME_VERSION_FOREVER,
                'display_order'                => 6,
                'name'                         => 'gameversions.forever.name',
                'description'                  => 'gameversions.forever.description',
                'has_seasons'                  => false,
                'has_compendium'               => false,
                'active'                       => true,
                'retired_into_game_version_id' => null,
            ],
            [
                'expansion_id'                 => Expansion::ALL[Expansion::EXPANSION_TBC],
                'parent_game_version_id'       => GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA],
                'key'                          => GameVersion::GAME_VERSION_TBC,
                'display_order'                => 3,
                'name'                         => 'gameversions.tbc.name',
                'description'                  => 'gameversions.tbc.description',
                'has_seasons'                  => false,
                'has_compendium'               => false,
                'active'                       => true,
                'retired_into_game_version_id' => null,
            ],
            [
                'expansion_id'                 => Expansion::ALL[Expansion::EXPANSION_CLASSIC],
                'parent_game_version_id'       => GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA],
                'key'                          => GameVersion::GAME_VERSION_SOD,
                'display_order'                => 4,
                'name'                         => 'gameversions.sod.name',
                'description'                  => 'gameversions.sod.description',
                'has_seasons'                  => false,
                'has_compendium'               => false,
                'active'                       => true,
                'retired_into_game_version_id' => null,
            ],
        ];

        GameVersion::from(DatabaseSeeder::getTempTableName(GameVersion::class))->insert($gameVersionAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [GameVersion::class];
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
