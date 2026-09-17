<?php

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Service\CombatLog\Enums\EnemyResolutionHeatmapMetric;
use Illuminate\Support\Collection;

/**
 * @var Dungeon              $dungeon
 * @var MappingVersion       $mappingVersion                 The mapping version the resolutions are filtered to
 * @var Collection<int, int> $mappingVersionResolutionCounts mapping_version_id => resolution count
 * @var Collection<int, int> $npcResolutionCounts            npc_id => resolution count, for $mappingVersion
 * @var Collection<int, Npc> $npcs                           The npcs to offer in the filter, most resolutions first
 */

$npcSuffixes = $npcs->mapWithKeys(static fn(Npc $npc) => [
    $npc->id => sprintf(' — %s', number_format($npcResolutionCounts->get($npc->id, 0))),
])->all();

$minDistanceRecorded = (int)config('keystoneguru.enemy_resolution.record_min_distance_yd');
?>
@include('common.general.inline', ['path' => 'common/maps/combatlogrouteenemyresolutions', 'options' => [
    'dungeonId'                      => $dungeon->id,
    'mappingVersionId'               => $mappingVersion->id,
    'pageUrl'                        => route('admin.tools.combatlog.route.enemy_resolutions.view'),
    'getEnemyResolutionsUrl'         => route('ajax.admin.combatlogroute.enemy_resolutions'),
    'linesUrl'                       => route('ajax.admin.combatlogroute.enemy_resolutions.lines'),
    'showLinesSelector'              => '#combatlogroute_enemy_resolutions_show_lines',
    'linePopupTexts'                 => [
        'npc'           => __('view_common.maps.controls.combatlogrouteenemyresolutions.line_popup.npc'),
        'distance'      => __('view_common.maps.controls.combatlogrouteenemyresolutions.line_popup.distance'),
        'weighted'      => __('view_common.maps.controls.combatlogrouteenemyresolutions.line_popup.weighted'),
        'route'         => __('view_common.maps.controls.combatlogrouteenemyresolutions.line_popup.route'),
        'importedRoute' => __('view_common.maps.controls.combatlogrouteenemyresolutions.line_popup.imported_route'),
        'noRoute'       => __('view_common.maps.controls.combatlogrouteenemyresolutions.line_popup.no_route'),
    ],
    'deleteUrl'                      => route('ajax.admin.combatlogroute.enemy_resolutions.delete'),
    'filterMappingVersionIdSelector' => '#combatlogroute_enemy_resolutions_filter_mapping_version_id',
    'filterNpcIdSelector'            => '#combatlogroute_enemy_resolutions_filter_npc_id',
    'filterMetricSelector'           => '#combatlogroute_enemy_resolutions_filter_metric',
    'filterMinDistanceSelector'      => '#combatlogroute_enemy_resolutions_filter_min_distance',
    'clearButtonSelector'            => '#combatlogroute_enemy_resolutions_clear',
    'summarySelector'                => '#combatlogroute_enemy_resolutions_summary',
    'routesContainerSelector'        => '#combatlogroute_enemy_resolutions_routes',
    'routesListSelector'             => '#combatlogroute_enemy_resolutions_routes_list',
    'summaryText'                    => __('view_common.maps.controls.combatlogrouteenemyresolutions.summary'),
    'dependencies'                   => ['common/maps/map'],
]])

<nav id="combatlogroute_enemy_resolutions_sidebar" class="route_sidebar top right row g-0 map_fade_out active">
    <div class="bg-header p-2">
        <h5 class="mb-3">{{ __($dungeon->name) }}</h5>

        <div class="small text-muted mb-3">
            {{ __('view_common.maps.controls.combatlogrouteenemyresolutions.explanation', ['distance' => $minDistanceRecorded]) }}
        </div>

        <div class="mb-3">
            {{ html()->label(__('view_common.maps.controls.combatlogrouteenemyresolutions.mapping_version_filter'), 'combatlogroute_enemy_resolutions_filter_mapping_version_id') }}
            <select id="combatlogroute_enemy_resolutions_filter_mapping_version_id" class="form-control selectpicker">
                @foreach($dungeon->mappingVersions as $dungeonMappingVersion)
                    <option value="{{ $dungeonMappingVersion->id }}"
                            @if($dungeonMappingVersion->id === $mappingVersion->id) selected="selected" @endif>
                        {{ __($dungeonMappingVersion->isLatestForDungeon() ? 'view_common.mappingversion.select.mapping_version' : 'view_common.mappingversion.select.mapping_version_previous', [
                            'gameVersion' => __($dungeonMappingVersion->gameVersion->name),
                            'version'     => $dungeonMappingVersion->version,
                        ]) }} — {{ number_format($mappingVersionResolutionCounts->get($dungeonMappingVersion->id, 0)) }}
                    </option>
                @endforeach
            </select>
        </div>

        @include('common.npc.select', [
            'id'       => 'combatlogroute_enemy_resolutions_filter_npc_id',
            'npcs'     => $npcs,
            'label'    => __('view_common.maps.controls.combatlogrouteenemyresolutions.npc_filter'),
            'multiple' => true,
            'required' => false,
            'showId'   => true,
            'suffixes' => $npcSuffixes,
        ])

        <div class="mb-3">
            {{ html()->label(__('view_common.maps.controls.combatlogrouteenemyresolutions.metric_filter'), 'combatlogroute_enemy_resolutions_filter_metric') }}
            <select id="combatlogroute_enemy_resolutions_filter_metric" class="form-control selectpicker">
                @foreach(EnemyResolutionHeatmapMetric::cases() as $metric)
                    <option value="{{ $metric->value }}">{{ $metric->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            {{ html()->label(__('view_common.maps.controls.combatlogrouteenemyresolutions.min_distance_filter'), 'combatlogroute_enemy_resolutions_filter_min_distance') }}
            <input type="number" min="{{ $minDistanceRecorded }}" step="5"
                   id="combatlogroute_enemy_resolutions_filter_min_distance"
                   class="form-control"
                   placeholder="{{ $minDistanceRecorded }}">
        </div>

        <div class="form-check mb-1">
            <input type="checkbox" class="form-check-input" id="combatlogroute_enemy_resolutions_show_lines" checked>
            <label class="form-check-label" for="combatlogroute_enemy_resolutions_show_lines">
                {{ __('view_common.maps.controls.combatlogrouteenemyresolutions.show_lines') }}
            </label>
        </div>
        <div class="small text-muted mb-2">
            {{ __('view_common.maps.controls.combatlogrouteenemyresolutions.lines_legend', ['count' => \App\Http\Requests\Ajax\AjaxAdminCombatLogRouteGetEnemyResolutionLinesFormRequest::LIMIT_DEFAULT]) }}
        </div>

        <div id="combatlogroute_enemy_resolutions_summary" class="small mb-2"></div>

        <button id="combatlogroute_enemy_resolutions_clear"
                class="btn btn-danger w-100 mt-2"
                data-bs-toggle="tooltip"
                title="{{ __('view_common.maps.controls.combatlogrouteenemyresolutions.clear_resolutions_title') }}">
            <i class="fas fa-trash"></i>
            {{ __('view_common.maps.controls.combatlogrouteenemyresolutions.clear_resolutions') }}
        </button>

        <div id="combatlogroute_enemy_resolutions_routes" class="mt-3" style="display: none;">
            <h6>{{ __('view_common.maps.controls.combatlogrouteenemyresolutions.matching_routes') }}</h6>
            <div id="combatlogroute_enemy_resolutions_routes_list"></div>
        </div>
    </div>
</nav>
