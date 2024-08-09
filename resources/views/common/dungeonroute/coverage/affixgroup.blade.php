<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * @var Collection<Dungeon>      $dungeons
 * @var Collection<AffixGroup>   $affixgroups
 * @var Collection<DungeonRoute> $dungeonRoutes
 * @var AffixGroup               $currentAffixGroup
 * @var Season                   $currentSeason
 * @var Season|null              $nextSeason
 * @var Season|null              $selectedSeason
 */

if (!function_exists('getDungeonRoutesByDungeonIdAndAffixGroupId')) {
    /**
     * @return Collection
     */
    function getDungeonRoutesByDungeonIdAndAffixGroupId(Collection $dungeonRoutes, Dungeon $dungeon, AffixGroup $affixGroup): Collection
    {
        if ($dungeonRoutes->has($dungeon->id)) {
            /** @var Collection $dungeonRoutesList */
            $dungeonRoutesList = $dungeonRoutes->get($dungeon->id);
            $result            = $dungeonRoutesList->filter(
                static fn(DungeonRoute $dungeonRoute) => $dungeonRoute->affixes->filter(
                    static fn(AffixGroup $affixGroupCandidate) => $affixGroupCandidate->id === $affixGroup->id
                )->isNotEmpty()
            );
        } else {
            $result = collect();
        }

        return $result;
    }
}

// Build a list of seasons that we use to make selections of
$seasons = [];
if ($nextSeason !== null) {
    $seasons[] = $nextSeason;
}

$routeCoverageSeasonId = $_COOKIE['dungeonroute_coverage_season_id'] ?? $currentSeason->id;
$seasons[]             = $currentSeason;

$seasonSelect = collect($seasons)->pluck('name_long', 'id')->mapWithKeys(static fn($name, $id) => [$id => __($name)]);
?>
@include('common.general.inline', ['path' => 'common/dungeonroute/coverage/affixgroup',
    'dependencies' => [
        'dungeonroute/table',
        'common/group/affixes',
    ],
    'options' => [

    ],
])

<div id="dungeonroute_coverage_affixgroup">
    <table id="dungeonroute_coverage_affixgroup_table" class="bg-secondary" style="width: 100%">
        <thead>
        <tr>
            <th class="px-1">
                @if( $seasonSelect->count() === 1 )
                    <div class="text-center">
                        {{ $seasonSelect->first() }}
                    </div>
                @else
                    {!! Form::select('dungeonroute_coverage_season_id', $seasonSelect->toArray(), $routeCoverageSeasonId,
                            ['id' => 'dungeonroute_coverage_season_id', 'class' => 'form-control selectpicker']
                    ) !!}
                @endif
            </th>
            @foreach($affixgroups as $affixGroup)
                <th class="p-1 {{ $currentAffixGroup->id === $affixGroup->id ? 'bg-success' : '' }}">
                    @include('common.affixgroup.affixgroup', [
                        'affixgroup' => $affixGroup,
                        'showText' => false,
                        'cols' => $selectedSeason->seasonal_affix_id === null ? 1 : 2,
                        'center' => true,
                    ])
                </th>
            @endforeach
        </tr>
        </thead>
        <tbody class="text-center">
        @foreach($dungeons as $dungeon)
            <tr class="{{ $loop->index % 2 === 0 ? 'odd' : 'even' }}">
                <td class="p-1">
                    {{ __($dungeon->name) }}
                </td>
                @foreach($affixgroups as $affixGroup)
                        <?php
                        /** @var Dungeon $dungeon */
                        /** @var AffixGroup $affixGroup */

                        $availableDungeonRoutes = getDungeonRoutesByDungeonIdAndAffixGroupId($dungeonRoutes, $dungeon, $affixGroup);
                        $hasEnemyForces         = $availableDungeonRoutes->filter(
                            static fn(DungeonRoute $dungeonRoute) => (bool)$dungeonRoute->has_enemy_forces
                        )->isNotEmpty();
                        ?>
                    <td
                        @if($availableDungeonRoutes->isNotEmpty())
                            class="{{ $hasEnemyForces ? 'covered' : 'covered_warning' }}"
                        @endif
                    >
                        @if($availableDungeonRoutes->isNotEmpty())
                            <div class="dungeonroute_coverage_filter_select">
                                <button class="btn btn-sm w-100"
                                        data-dungeon-id="{{ $dungeon->id }}"
                                        data-affix-group-id="{{ $affixGroup->id }}">
                                    <i class="fa fa-check {{ $hasEnemyForces ? 'text-success' : 'text-warning' }}">

                                    </i>
                                </button>
                            </div>
                        @else
                            <div class="dungeonroute_coverage_new_dungeon_route">
                                <button class="btn btn-sm w-100 new_route_style_create_create"
                                        data-dungeon-id="{{ $dungeon->id }}"
                                        data-affix-group-id="{{ $affixGroup->id }}"
                                        data-toggle="modal"
                                        data-target="#create_route_modal"
                                        style="display: {{ $newRouteStyle === 'create' ? 'block' : 'none' }}"
                                >
                                    <i class="fa fa-plus text-info">

                                    </i>
                                </button>
                            </div>
                            <div class="dungeonroute_coverage_search_dungeon_route">
                                <a class="btn btn-sm w-100 new_route_style_create_search"
                                   style="display: {{ $newRouteStyle === 'search' ? 'block' : 'none' }}"
                                   href="{{ route('dungeonroutes.search', [
                                                'season' => $selectedSeason->id,
                                                'affixgroups' => $affixGroup->id,
                                                ]) }}&dungeons={{ $dungeon->id }}"
                                >
                                    <i class="fa fa-search text-info">

                                    </i>
                                </a>
                            </div>
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
