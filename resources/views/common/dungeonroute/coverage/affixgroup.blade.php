<?php

/** @var $dungeons \Illuminate\Support\Collection|\App\Models\Dungeon[] */
/** @var $affixgroups \Illuminate\Support\Collection|\App\Models\AffixGroup\AffixGroup[] */
/** @var $dungeonRoutes \Illuminate\Support\Collection|\App\Models\DungeonRoute[] */
/** @var $currentExpansion \App\Models\Expansion */
/** @var $currentAffixGroup \App\Models\AffixGroup\AffixGroup */

/**
 * @param \Illuminate\Support\Collection $dungeonRoutes
 * @param \App\Models\Dungeon $dungeon
 * @param \App\Models\AffixGroup\AffixGroup $affixGroup
 * @return \Illuminate\Support\Collection
 */
function getDungeonRoutesByDungeonIdAndAffixGroupId(\Illuminate\Support\Collection $dungeonRoutes, \App\Models\Dungeon $dungeon, \App\Models\AffixGroup\AffixGroup $affixGroup):
\Illuminate\Support\Collection
{
    if ($dungeonRoutes->has($dungeon->id)) {
        $result = $dungeonRoutes->get($dungeon->id)->filter(function (\App\Models\DungeonRoute $dungeonRoute) use ($affixGroup) {
            return $dungeonRoute->affixes->filter(function (\App\Models\AffixGroup\AffixGroup $affixGroupCandidate) use ($affixGroup) {
                return $affixGroupCandidate->id === $affixGroup->id;
            })->isNotEmpty();
        });
    } else {
        $result = collect();
    }

    return $result;
}

?>
@include('common.general.inline', ['path' => 'common/dungeonroute/coverage/affixgroup',
    'dependencies' => [
        'dungeonroute/table',
        'common/group/affixes',
    ],
    'options' => [

    ]
])

<div id="dungeonroute_coverage_affixgroup">
    {{--    <h5 class="mb-0 text-center">--}}
    {{--        <a href="#" class="btn btn-link" data-toggle="collapse"--}}
    {{--           data-target="#dungeonroute_coverage_affixgroup_collapse"--}}
    {{--           aria-expanded="false" aria-controls="dungeonroute_coverage_affixgroup_collapse">--}}
    {{--            {{ __('Affix coverage') }}--}}
    {{--        </a>--}}
    {{--    </h5>--}}

    {{--    <div id="dungeonroute_coverage_affixgroup_collapse" class="collapse show"--}}
    {{--         aria-labelledby="dungeonroute_coverage_affixgroup_header"--}}
    {{--         data-parent="#dungeonroute_coverage_affixgroup">--}}
    <table id="dungeonroute_coverage_affixgroup_table" class="bg-secondary" style="width: 100%">
        <thead>
        <tr>
            <th>
            </th>
            @foreach($affixgroups as $affixGroup)
                <th class="p-1 {{ $currentAffixGroup->id === $affixGroup->id ? 'bg-success' : '' }}">
                    @include('common.affixgroup.affixgroup', ['affixgroup' => $affixGroup, 'showText' => false, 'cols' => 2])
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
                    <td>
                        <?php
                        /** @var \App\Models\Dungeon $dungeon */
                        /** @var \App\Models\AffixGroup\AffixGroup $affixGroup */

                        $availableDungeonRoutes = getDungeonRoutesByDungeonIdAndAffixGroupId($dungeonRoutes, $dungeon, $affixGroup);
                        $hasEnemyForces = $availableDungeonRoutes->filter(function (\App\Models\DungeonRoute $dungeonRoute) {
                            return (bool)$dungeonRoute->has_enemy_forces;
                        })->isNotEmpty();
                        ?>
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
                                                'expansion' => $currentExpansion->shortname,
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
    {{--    </div>--}}
</div>
