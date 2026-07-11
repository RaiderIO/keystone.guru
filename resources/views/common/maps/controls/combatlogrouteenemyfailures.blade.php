<?php

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;

/**
 * @var Dungeon        $dungeon
 * @var MappingVersion $mappingVersion
 */

$npcIds = $mappingVersion->enemies()
    ->whereNotNull('npc_id')
    ->select('npc_id')
    ->distinct()
    ->pluck('npc_id');
$npcs = Npc::query()->whereIn('id', $npcIds)->orderBy('name')->get();
?>
@include('common.general.inline', ['path' => 'common/maps/combatlogrouteenemyfailures', 'options' => [
    'dungeonId'               => $dungeon->id,
    'getEnemyFailuresUrl'     => route('ajax.admin.combatlogroute.enemy_failures'),
    'deleteUrl'               => route('ajax.admin.combatlogroute.enemy_failures.delete'),
    'filterNpcIdSelector'     => '#combatlogroute_enemy_failures_filter_npc_id',
    'clearButtonSelector'     => '#combatlogroute_enemy_failures_clear',
    'routesContainerSelector' => '#combatlogroute_enemy_failures_routes',
    'routesListSelector'      => '#combatlogroute_enemy_failures_routes_list',
    'noMatchingRoutesText'    => __('view_common.maps.controls.combatlogrouteenemyfailures.no_matching_routes'),
    'dependencies'            => ['common/maps/map'],
]])

<nav id="combatlogroute_enemy_failures_sidebar" class="route_sidebar top right row g-0 map_fade_out active">
    <div class="bg-header p-2">
        <h5 class="mb-3">{{ __($dungeon->name) }}</h5>

        @include('common.npc.select', [
            'id'       => 'combatlogroute_enemy_failures_filter_npc_id',
            'npcs'     => $npcs,
            'label'    => __('view_common.maps.controls.combatlogrouteenemyfailures.npc_filter'),
            'multiple' => true,
            'required' => false,
            'showId'   => true,
        ])

        <button id="combatlogroute_enemy_failures_clear"
                class="btn btn-danger w-100 mt-2"
                data-bs-toggle="tooltip"
                title="{{ __('view_common.maps.controls.combatlogrouteenemyfailures.clear_failures_title') }}">
            <i class="fas fa-trash"></i>
            {{ __('view_common.maps.controls.combatlogrouteenemyfailures.clear_failures') }}
        </button>

        <div id="combatlogroute_enemy_failures_routes" class="mt-3" style="display: none;">
            <h6>{{ __('view_common.maps.controls.combatlogrouteenemyfailures.matching_routes') }}</h6>
            <div id="combatlogroute_enemy_failures_routes_list"></div>
        </div>
    </div>
</nav>
