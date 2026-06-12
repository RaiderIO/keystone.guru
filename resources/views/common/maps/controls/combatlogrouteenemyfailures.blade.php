<?php

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;

/**
 * @var Dungeon        $dungeon
 * @var MappingVersion $mappingVersion
 */
?>
@include('common.general.inline', ['path' => 'common/maps/combatlogrouteenemyfailures', 'options' => [
    'dungeonId'            => $dungeon->id,
    'getEnemyFailuresUrl'  => route('ajax.admin.combatlogroute.enemy_failures'),
    'deleteUrl'            => route('ajax.admin.combatlogroute.enemy_failures.delete'),
    'filterNpcIdSelector'  => '#combatlogroute_enemy_failures_filter_npc_id',
    'clearButtonSelector'  => '#combatlogroute_enemy_failures_clear',
    'dependencies'         => ['common/maps/map'],
]])

<nav id="combatlogroute_enemy_failures_sidebar" class="route_sidebar top right row no-gutters map_fade_out active">
    <div class="bg-header p-2">
        <h5 class="mb-3">{{ __($dungeon->name) }}</h5>

        @component('common.forms.labelinput', [
            'name'  => 'npc_id',
            'label' => __('view_common.maps.controls.combatlogrouteenemyfailures.npc_id_filter'),
        ])
            <input id="combatlogroute_enemy_failures_filter_npc_id"
                   type="text"
                   class="form-control"
                   placeholder="{{ __('view_common.maps.controls.combatlogrouteenemyfailures.npc_id_filter_placeholder') }}">
        @endcomponent

        <button id="combatlogroute_enemy_failures_clear"
                class="btn btn-danger w-100 mt-2"
                data-toggle="tooltip"
                title="{{ __('view_common.maps.controls.combatlogrouteenemyfailures.clear_failures_title') }}">
            <i class="fas fa-trash"></i>
            {{ __('view_common.maps.controls.combatlogrouteenemyfailures.clear_failures') }}
        </button>
    </div>
</nav>
