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
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Adding known Seasons');

        /** @var Collection|Expansion[] $expansions */
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
                'dungeons'                => $dungeonsByExpansion->get($expansions->get(Expansion::EXPANSION_BFA)),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_BFA),
                'seasonal_affix_id'       => 16,
                'index'                   => 2,
                'start'                   => '2019-01-23 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 12,
                'start_affix_group_index' => 4,
                'dungeons'                => $dungeonsByExpansion->get($expansions->get(Expansion::EXPANSION_BFA)),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_BFA),
                'seasonal_affix_id'       => 17,
                'index'                   => 3,
                'start'                   => '2019-07-10 00:00:00',
                'presets'                 => 3,
                'affix_group_count'       => 12,
                'start_affix_group_index' => 8,
                'dungeons'                => $dungeonsByExpansion->get($expansions->get(Expansion::EXPANSION_BFA)),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_BFA),
                'seasonal_affix_id'       => 18,
                'index'                   => 4,
                'start'                   => '2020-01-21 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 12,
                'start_affix_group_index' => 0,
                'dungeons'                => $dungeonsByExpansion->get($expansions->get(Expansion::EXPANSION_BFA)),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_SHADOWLANDS),
                'seasonal_affix_id'       => 22,
                'index'                   => 1,
                'start'                   => '2020-12-08 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 12,
                'start_affix_group_index' => 11,
                'dungeons'                => $dungeonsByExpansion->get($expansions->get(Expansion::EXPANSION_SHADOWLANDS)),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_SHADOWLANDS),
                'seasonal_affix_id'       => 23,
                'index'                   => 2,
                'start'                   => '2021-07-06 00:00:00',
                'presets'                 => 2,
                'affix_group_count'       => 12,
                'start_affix_group_index' => 4,
                'dungeons'                => $dungeonsByExpansion->get($expansions->get(Expansion::EXPANSION_SHADOWLANDS)),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_LEGION),
                'seasonal_affix_id'       => 25,
                'index'                   => 1,
                'start'                   => '2021-12-28 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 12,
                'start_affix_group_index' => 0,
                'dungeons'                => $dungeonsByExpansion->get($expansions->get(Expansion::EXPANSION_LEGION)),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_SHADOWLANDS),
                'seasonal_affix_id'       => 26,
                'index'                   => 3,
                'start'                   => '2022-03-01 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 12,
                'start_affix_group_index' => 2,
                'dungeons'                => $dungeonsByExpansion->get($expansions->get(Expansion::EXPANSION_SHADOWLANDS)),
            ], [
                'expansion_id'            => $expansions->get(Expansion::EXPANSION_SHADOWLANDS),
                'seasonal_affix_id'       => 26,
                'index'                   => 4,
                'start'                   => '2022-08-02 00:00:00',
                'presets'                 => 0,
                'affix_group_count'       => 12,
                'start_affix_group_index' => 0,
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

        Season::insert($seasonAttributes);
        SeasonDungeon::insert($seasonDungeonAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [
            Season::class,
            SeasonDungeon::class,
        ];
    }
}
