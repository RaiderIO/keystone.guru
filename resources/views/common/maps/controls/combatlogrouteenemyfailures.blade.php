<?php

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Service\CombatLog\Dtos\EnemyFailureAnalysis\EnemyFailureVerdict;
use Illuminate\Support\Collection;

/**
 * @var Dungeon              $dungeon
 * @var MappingVersion       $mappingVersion              The mapping version the failures are filtered to
 * @var Collection<int, int> $mappingVersionFailureCounts mapping_version_id => failure count
 * @var Collection<int, int> $npcFailureCounts            npc_id => failure count, for $mappingVersion
 * @var Collection<int, Npc> $npcs                        The npcs to offer in the filter, most failures first
 */

$mappedNpcIds = $mappingVersion->enemies()
    ->whereNotNull('npc_id')
    ->distinct()
    ->pluck('npc_id')
    ->flip();

$npcSuffixes = $npcs->mapWithKeys(static function (Npc $npc) use ($npcFailureCounts, $mappedNpcIds) {
    $suffix = sprintf(' — %s', number_format($npcFailureCounts->get($npc->id, 0)));
    if (!$mappedNpcIds->has($npc->id)) {
        $suffix .= sprintf(' ⚠ %s', __('view_common.maps.controls.combatlogrouteenemyfailures.not_mapped'));
    }

    return [$npc->id => $suffix];
})->all();
?>
@include('common.general.inline', ['path' => 'common/maps/combatlogrouteenemyfailures', 'options' => [
    'dungeonId'                      => $dungeon->id,
    'mappingVersionId'               => $mappingVersion->id,
    'pageUrl'                        => route('admin.tools.combatlog.route.enemy_failures.view'),
    'getEnemyFailuresUrl'            => route('ajax.admin.combatlogroute.enemy_failures'),
    'clustersUrl'                    => route('ajax.admin.combatlogroute.enemy_failures.clusters'),
    'showClustersSelector'           => '#combatlogroute_enemy_failures_show_clusters',
    'verdicts'                       => collect(EnemyFailureVerdict::cases())
        ->mapWithKeys(static fn(EnemyFailureVerdict $verdict) => [$verdict->value => ['label' => $verdict->label(), 'color' => $verdict->color()]])
        ->all(),
    'clusterPopupTexts'              => [
        'failures'     => __('view_common.maps.controls.combatlogrouteenemyfailures.cluster_popup.failures'),
        'nearestEnemy' => __('view_common.maps.controls.combatlogrouteenemyfailures.cluster_popup.nearest_enemy'),
        'nearestNone'  => __('view_common.maps.controls.combatlogrouteenemyfailures.cluster_popup.nearest_none'),
        'inRange'      => __('view_common.maps.controls.combatlogrouteenemyfailures.cluster_popup.in_range'),
        'seen'         => __('view_common.maps.controls.combatlogrouteenemyfailures.cluster_popup.seen'),
        'filterNpc'    => __('view_common.maps.controls.combatlogrouteenemyfailures.cluster_popup.filter_npc'),
    ],
    'deleteUrl'                      => route('ajax.admin.combatlogroute.enemy_failures.delete'),
    'filterMappingVersionIdSelector' => '#combatlogroute_enemy_failures_filter_mapping_version_id',
    'filterNpcIdSelector'            => '#combatlogroute_enemy_failures_filter_npc_id',
    'clearButtonSelector'            => '#combatlogroute_enemy_failures_clear',
    'routesContainerSelector'        => '#combatlogroute_enemy_failures_routes',
    'routesListSelector'             => '#combatlogroute_enemy_failures_routes_list',
    'noMatchingRoutesText'           => __('view_common.maps.controls.combatlogrouteenemyfailures.no_matching_routes'),
    'dependencies'                   => ['common/maps/map'],
]])

<nav id="combatlogroute_enemy_failures_sidebar" class="route_sidebar top right row g-0 map_fade_out active">
    <div class="bg-header p-2">
        <h5 class="mb-3">{{ __($dungeon->name) }}</h5>

        <div class="mb-3">
            {{ html()->label(__('view_common.maps.controls.combatlogrouteenemyfailures.mapping_version_filter'), 'combatlogroute_enemy_failures_filter_mapping_version_id') }}
            <select id="combatlogroute_enemy_failures_filter_mapping_version_id" class="form-control selectpicker">
                @foreach($dungeon->mappingVersions as $dungeonMappingVersion)
                    <option value="{{ $dungeonMappingVersion->id }}"
                            @if($dungeonMappingVersion->id === $mappingVersion->id) selected="selected" @endif>
                        {{ __($dungeonMappingVersion->isLatestForDungeon() ? 'view_common.mappingversion.select.mapping_version' : 'view_common.mappingversion.select.mapping_version_previous', [
                            'gameVersion' => __($dungeonMappingVersion->gameVersion->name),
                            'version'     => $dungeonMappingVersion->version,
                        ]) }} — {{ number_format($mappingVersionFailureCounts->get($dungeonMappingVersion->id, 0)) }}
                    </option>
                @endforeach
            </select>
        </div>

        @include('common.npc.select', [
            'id'       => 'combatlogroute_enemy_failures_filter_npc_id',
            'npcs'     => $npcs,
            'label'    => __('view_common.maps.controls.combatlogrouteenemyfailures.npc_filter'),
            'multiple' => true,
            'required' => false,
            'showId'   => true,
            'suffixes' => $npcSuffixes,
        ])

        <div class="form-check mb-1">
            <input type="checkbox" class="form-check-input" id="combatlogroute_enemy_failures_show_clusters" checked>
            <label class="form-check-label" for="combatlogroute_enemy_failures_show_clusters">
                {{ __('view_common.maps.controls.combatlogrouteenemyfailures.show_clusters') }}
            </label>
        </div>
        <div class="small mb-2">
            <div class="text-muted">{{ __('view_common.maps.controls.combatlogrouteenemyfailures.clusters_legend') }}
                ({{ __('view_common.maps.controls.combatlogrouteenemyfailures.cluster_low_volume') }})</div>
            @foreach(EnemyFailureVerdict::cases() as $verdict)
                <div>
                    <span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background-color: {{ $verdict->color() }}"></span>{{ $verdict->label() }}
                </div>
            @endforeach
        </div>

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
