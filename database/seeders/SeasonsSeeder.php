<?php

namespace Database\Seeders;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Season;
use App\Models\SeasonDungeon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class SeasonsSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var Collection<Expansion> $expansions */
        $expansions = Expansion::all()->mapWithKeys(function (Expansion $expansion) {
            return [$expansion->shortname => $expansion->id];
        });

        $dungeonsByExpansion = Dungeon::all()->groupBy('expansion_id', true);

        $seasonAttributes = [
            [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_BFA),
                'seasonal_affix_id'       => 6,
                'index'                   => 1,
                'start'                   => '2018-09-04 00:00:00',
                'presets'                 => 3,
                'affix_group_count'       => 12,
                'start_affix_group_index' => 0,
                'key_level_min'           => 2,
                'key_level_max'           => 25,
                'item_level_min'          => null,
                'item_level_max'          => null,
                'dungeons'                => $dungeonsByExpansion->get($expansions->get(Expansion::EXPANSION_BFA)),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_BFA),
                'seasonal_affix_id'       => 16,
                'index'                   => 2,
                'start'                   => '2019-01-23 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 12,
                'start_affix_group_index' => 4,
                'key_level_min'           => 2,
                'key_level_max'           => 25,
                'item_level_min'          => null,
                'item_level_max'          => null,
                'dungeons'                => $dungeonsByExpansion->get($expansions->get(Expansion::EXPANSION_BFA)),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_BFA),
                'seasonal_affix_id'       => 17,
                'index'                   => 3,
                'start'                   => '2019-07-10 00:00:00',
                'presets'                 => 3,
                'affix_group_count'       => 12,
                'start_affix_group_index' => 8,
                'key_level_min'           => 2,
                'key_level_max'           => 25,
                'item_level_min'          => null,
                'item_level_max'          => null,
                'dungeons'                => $dungeonsByExpansion->get($expansions->get(Expansion::EXPANSION_BFA)),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_BFA),
                'seasonal_affix_id'       => 18,
                'index'                   => 4,
                'start'                   => '2020-01-21 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 12,
                'start_affix_group_index' => 0,
                'key_level_min'           => 2,
                'key_level_max'           => 25,
                'item_level_min'          => null,
                'item_level_max'          => null,
                'dungeons'                => $dungeonsByExpansion->get($expansions->get(Expansion::EXPANSION_BFA)),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_SHADOWLANDS),
                'seasonal_affix_id'       => 22,
                'index'                   => 1,
                'start'                   => '2020-12-08 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 12,
                'start_affix_group_index' => 11,
                'key_level_min'           => 2,
                'key_level_max'           => 30,
                'item_level_min'          => null,
                'item_level_max'          => null,
                'dungeons'                => $dungeonsByExpansion->get($expansions->get(Expansion::EXPANSION_SHADOWLANDS)),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_SHADOWLANDS),
                'seasonal_affix_id'       => 23,
                'index'                   => 2,
                'start'                   => '2021-07-06 00:00:00',
                'presets'                 => 2,
                'affix_group_count'       => 12,
                'start_affix_group_index' => 4,
                'key_level_min'           => 2,
                'key_level_max'           => 30,
                'item_level_min'          => null,
                'item_level_max'          => null,
                'dungeons'                => $dungeonsByExpansion->get($expansions->get(Expansion::EXPANSION_SHADOWLANDS)),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_LEGION),
                'seasonal_affix_id'       => 25,
                'index'                   => 1,
                'start'                   => '2050-12-28 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 12,
                'start_affix_group_index' => 0,
                'key_level_min'           => 2,
                'key_level_max'           => 30,
                'item_level_min'          => null,
                'item_level_max'          => null,
                'dungeons'                => $dungeonsByExpansion->get($expansions->get(Expansion::EXPANSION_LEGION)),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_SHADOWLANDS),
                'seasonal_affix_id'       => 26,
                'index'                   => 3,
                'start'                   => '2022-03-01 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 12,
                'start_affix_group_index' => 2,
                'key_level_min'           => 2,
                'key_level_max'           => 30,
                'item_level_min'          => null,
                'item_level_max'          => null,
                'dungeons'                => $dungeonsByExpansion->get($expansions->get(Expansion::EXPANSION_SHADOWLANDS)),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_SHADOWLANDS),
                'seasonal_affix_id'       => 26,
                'index'                   => 4,
                'start'                   => '2022-08-02 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 12,
                'start_affix_group_index' => 0,
                'key_level_min'           => 2,
                'key_level_max'           => 30,
                'item_level_min'          => null,
                'item_level_max'          => null,
                'dungeons'                => Dungeon::select('dungeons.*')
                    ->join('expansions', 'dungeons.expansion_id', 'expansions.id')
                    ->whereIn('key', [
                        Dungeon::DUNGEON_TAZAVESH_SO_LEAHS_GAMBIT,
                        Dungeon::DUNGEON_TAZAVESH_STREETS_OF_WONDER,
                        Dungeon::DUNGEON_MECHAGON_JUNKYARD,
                        Dungeon::DUNGEON_MECHAGON_WORKSHOP,
                        Dungeon::DUNGEON_LOWER_KARAZHAN,
                        Dungeon::DUNGEON_UPPER_KARAZHAN,
                        Dungeon::DUNGEON_GRIMRAIL_DEPOT,
                        Dungeon::DUNGEON_IRON_DOCKS,
                    ])->orderBy('expansions.released_at')
                    ->get(),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_DRAGONFLIGHT),
                'seasonal_affix_id'       => 26,
                'index'                   => 1,
                'start'                   => '2022-12-12 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 10,
                'start_affix_group_index' => 3,
                'key_level_min'           => 2,
                'key_level_max'           => 30,
                'item_level_min'          => null,
                'item_level_max'          => null,
                'dungeons'                => Dungeon::select('dungeons.*')
                    ->join('expansions', 'dungeons.expansion_id', 'expansions.id')
                    ->whereIn('key', [
                        Dungeon::DUNGEON_ALGETH_AR_ACADEMY,
                        Dungeon::DUNGEON_THE_NOKHUD_OFFENSIVE,
                        Dungeon::DUNGEON_RUBY_LIFE_POOLS,
                        Dungeon::DUNGEON_THE_AZURE_VAULT,
                        Dungeon::DUNGEON_COURT_OF_STARS,
                        Dungeon::DUNGEON_HALLS_OF_VALOR,
                        Dungeon::DUNGEON_SHADOWMOON_BURIAL_GROUNDS,
                        Dungeon::DUNGEON_TEMPLE_OF_THE_JADE_SERPENT,
                    ])->orderBy('expansions.released_at')
                    ->get(),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_DRAGONFLIGHT),
                'seasonal_affix_id'       => null,
                'index'                   => 2,
                'start'                   => '2023-05-08 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 10,
                'start_affix_group_index' => 4,
                'key_level_min'           => 2,
                'key_level_max'           => 30,
                'item_level_min'          => null,
                'item_level_max'          => null,
                'dungeons'                => Dungeon::select('dungeons.*')
                    ->join('expansions', 'dungeons.expansion_id', 'expansions.id')
                    ->whereIn('dungeons.key', [
                        Dungeon::DUNGEON_HALLS_OF_INFUSION,
                        Dungeon::DUNGEON_BRACKENHIDE_HOLLOW,
                        Dungeon::DUNGEON_ULDAMAN_LEGACY_OF_TYR,
                        Dungeon::DUNGEON_NELTHARUS,
                        Dungeon::DUNGEON_NELTHARIONS_LAIR,
                        Dungeon::DUNGEON_FREEHOLD,
                        Dungeon::DUNGEON_THE_UNDERROT,
                        Dungeon::DUNGEON_THE_VORTEX_PINNACLE,
                    ])->orderBy('expansions.released_at')
                    ->get(),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_DRAGONFLIGHT),
                'seasonal_affix_id'       => null,
                'index'                   => 3,
                'start'                   => '2023-11-13 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 10,
                'start_affix_group_index' => 1,
                'key_level_min'           => 2,
                'key_level_max'           => 30,
                'item_level_min'          => null,
                'item_level_max'          => null,
                'dungeons'                => Dungeon::select('dungeons.*')
                    ->join('expansions', 'dungeons.expansion_id', 'expansions.id')
                    ->whereIn('dungeons.key', [
                        Dungeon::DUNGEON_DAWN_OF_THE_INFINITE_GALAKRONDS_FALL,
                        Dungeon::DUNGEON_DAWN_OF_THE_INFINITE_MUROZONDS_RISE,
                        Dungeon::DUNGEON_DARKHEART_THICKET,
                        Dungeon::DUNGEON_BLACK_ROOK_HOLD,
                        Dungeon::DUNGEON_WAYCREST_MANOR,
                        Dungeon::DUNGEON_ATAL_DAZAR,
                        Dungeon::DUNGEON_THE_EVERBLOOM,
                        Dungeon::DUNGEON_THRONE_OF_THE_TIDES,
                    ])->orderBy('expansions.released_at')
                    ->get(),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_DRAGONFLIGHT),
                'seasonal_affix_id'       => null,
                'index'                   => 4,
                'start'                   => '2024-04-22 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 10,
                'start_affix_group_index' => 4,
                'key_level_min'           => 2,
                'key_level_max'           => 22,
                'item_level_min'          => null,
                'item_level_max'          => null,
                'dungeons'                => Dungeon::select('dungeons.*')
                    ->join('expansions', 'dungeons.expansion_id', 'expansions.id')
                    ->whereIn('dungeons.key', [
                        Dungeon::DUNGEON_ALGETH_AR_ACADEMY,
                        Dungeon::DUNGEON_THE_NOKHUD_OFFENSIVE,
                        Dungeon::DUNGEON_RUBY_LIFE_POOLS,
                        Dungeon::DUNGEON_THE_AZURE_VAULT,
                        Dungeon::DUNGEON_HALLS_OF_INFUSION,
                        Dungeon::DUNGEON_BRACKENHIDE_HOLLOW,
                        Dungeon::DUNGEON_ULDAMAN_LEGACY_OF_TYR,
                        Dungeon::DUNGEON_NELTHARUS,
                    ])->orderBy('expansions.released_at')
                    ->get(),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_TWW),
                'seasonal_affix_id'       => null,
                'index'                   => 1,
                'start'                   => '2024-09-16 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 8,
                'start_affix_group_index' => 3,
                'key_level_min'           => 2,
                'key_level_max'           => 25,
                'item_level_min'          => 580,
                'item_level_max'          => 640,
                'dungeons'                => Dungeon::select('dungeons.*')
                    ->join('expansions', 'dungeons.expansion_id', 'expansions.id')
                    ->whereIn('dungeons.key', [
                        Dungeon::DUNGEON_THE_STONEVAULT,
                        Dungeon::DUNGEON_THE_DAWNBREAKER,
                        Dungeon::DUNGEON_ARA_KARA_CITY_OF_ECHOES,
                        Dungeon::DUNGEON_CITY_OF_THREADS,
                        Dungeon::DUNGEON_MISTS_OF_TIRNA_SCITHE,
                        Dungeon::DUNGEON_THE_NECROTIC_WAKE,
                        Dungeon::DUNGEON_SIEGE_OF_BORALUS,
                        Dungeon::DUNGEON_GRIM_BATOL,
                    ])->orderBy('expansions.released_at')
                    ->get(),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_TWW),
                'seasonal_affix_id'       => null,
                'index'                   => 2,
                'start'                   => '2025-03-03 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 8,
                'start_affix_group_index' => 3,
                'key_level_min'           => 2,
                'key_level_max'           => 25,
                'item_level_min'          => 620,
                'item_level_max'          => 680,
                'dungeons'                => Dungeon::select('dungeons.*')
                    ->join('expansions', 'dungeons.expansion_id', 'expansions.id')
                    ->whereIn('dungeons.key', [
                        Dungeon::DUNGEON_CINDERBREW_MEADERY,
                        Dungeon::DUNGEON_DARKFLAME_CLEFT,
                        Dungeon::DUNGEON_PRIORY_OF_THE_SACRED_FLAME,
                        Dungeon::DUNGEON_THE_ROOKERY,
                        Dungeon::DUNGEON_MECHAGON_WORKSHOP,
                        Dungeon::DUNGEON_OPERATION_FLOODGATE,
                        Dungeon::DUNGEON_THEATER_OF_PAIN,
                        Dungeon::DUNGEON_THE_MOTHERLODE,
                    ])->orderBy('expansions.released_at')
                    ->get(),
            ],
        ];

        $seasonDungeonAttributes = [];
        $seasonId                = 1;
        foreach ($seasonAttributes as &$season) {
            /** @var Dungeon[] $dungeons */
            $dungeons = $season['dungeons'];
            unset($season['dungeons']);

            foreach ($dungeons as $dungeon) {
                $seasonDungeonAttributes[] = [
                    'season_id'  => $seasonId,
                    'dungeon_id' => $dungeon->id,
                ];
            }

            $seasonId++;
        }

        Season::from(DatabaseSeeder::getTempTableName(Season::class))->insert($seasonAttributes);
        SeasonDungeon::from(DatabaseSeeder::getTempTableName(SeasonDungeon::class))->insert($seasonDungeonAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [
            Season::class,
            SeasonDungeon::class,
        ];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
