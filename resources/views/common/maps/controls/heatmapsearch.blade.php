<?php

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\CombatLog\CombatLogEvent;
use App\Models\Dungeon;
use App\Service\Season\Dtos\WeeklyAffixGroup;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * @var bool $showAds
 * @var Dungeon $dungeon
 * @var bool $embed
 * @var string $embedStyle
 * @var bool $isMobile
 * @var integer $defaultState
 * @var bool $hideOnMove
 * @var bool $showAllEnabled
 * @var Collection<AffixGroup> $allAffixGroupsByActiveExpansion
 * @var Collection<Affix> $featuredAffixesByActiveExpansion
 * @var int $keyLevelMin
 * @var int $keyLevelMax
 * @var Collection<WeeklyAffixGroup> $seasonWeeklyAffixGroups
 */

// By default, show it if we're not mobile, but allow overrides
$pullsSidebarState = (int)($_COOKIE['pulls_sidebar_state'] ?? 1);
$defaultState ??= $isMobile ? 0 : $pullsSidebarState;
$heatmapSearchEnabled = (bool)($_COOKIE['heatmap_search_enabled'] ?? 1);

$filterExpandedCookiePrefix = 'heatmap_search_expanded';
$expandedDataType = (bool)($_COOKIE[sprintf('%s_data_type', $filterExpandedCookiePrefix)] ?? 0); // Hide by default
$expandedKeyLevel = (bool)($_COOKIE[sprintf('%s_key_level', $filterExpandedCookiePrefix)] ?? 1);
$expandedAffixes = (bool)($_COOKIE[sprintf('%s_affixes', $filterExpandedCookiePrefix)] ?? 1);
$expandedAffixWeek = (bool)($_COOKIE[sprintf('%s_weekly_affix_groups', $filterExpandedCookiePrefix)] ?? 1);
$expandedDuration = (bool)($_COOKIE[sprintf('%s_duration', $filterExpandedCookiePrefix)] ?? 1);

$shouldShowHeatmapSearchSidebar = $defaultState === 1;
$hideOnMove ??= $isMobile;
$showAds ??= true;
/** @var Collection<AffixGroup> $affixGroups */
$affixGroups = $allAffixGroupsByActiveExpansion->get($dungeon->expansion->shortname);
/** @var Collection<Affix> $featuredAffixes */
$featuredAffixes = $featuredAffixesByActiveExpansion->get($dungeon->expansion->shortname);
?>
@include('common.general.inline', ['path' => 'common/maps/heatmapsearchsidebar', 'options' => [
    'stateCookie' => 'heatmap_search_sidebar_state',
    'defaultState' => $defaultState,
    'hideOnMove' => $hideOnMove,
    'currentFiltersSelector' => '#heatmap_search_options_current_filters',
    'loaderSelector' => '#heatmap_search_loader',
    'searchResultSelector' => '#heatmap_search_result',
    'searchResultDataDungeonRoutesSelector' => '#heatmap_search_result_data_dungeonroutes',

    'keyLevelMin' => $keyLevelMin,
    'keyLevelMax' => $keyLevelMax,
    'durationMin' => 5,
    'durationMax' => 60,

    'enabledStateCookie' => 'heatmap_search_enabled',
    'enabledStateSelector' => '#heatmap_search_toggle',
    'filterEventTypeContainerSelector' => '#filter_event_type_container',
    'filterEventTypeSelector' => 'input[name="event_type"]',
    'filterDataTypeContainerSelector' => '#filter_data_type_container',
    'filterDataTypeSelector' => 'input[name="data_type"]',
    'filterLevelSelector' => '#filter_level',
    'filterAffixGroupsSelector' => '#filter_affixes',
    'filterAffixesSelector' => '.select_icon.class_icon.selectable',
    'filterWeeklyAffixGroupsSelector' => '#filter_weekly_affix_groups',
    'filterDurationSelector' => '#filter_duration',

    'filterCollapseNames' => ['level', 'affixes', 'duration'],
    'filterCookiePrefix' => $filterExpandedCookiePrefix,

    'dependencies' => ['common/maps/map'],
    // Mobile sidebar options
    'sidebarSelector' => '#heatmap_search_sidebar',
    'sidebarToggleSelector' => '#heatmap_search_sidebar_trigger',
    'sidebarScrollSelector' => '#heatmap_search_sidebar .data_container',
    'anchor' => 'right',
    'edit' => $edit,
]])

@section('scripts')
    @parent

    @include('common.handlebars.affixgroupsselect', [
        'id' => 'filter_affixes',
        'affixgroups' => $affixGroups,
    ])

    @include('common.handlebars.affixweekselect', [
        'id' => 'filter_weekly_affix_groups',
        'seasonWeeklyAffixGroups' => $seasonWeeklyAffixGroups,
    ])
@endsection

<!--suppress HtmlFormInputWithoutLabel -->
<nav id="heatmap_search_sidebar"
     class="route_sidebar top right row no-gutters map_fade_out
     {{ $isMobile ? 'mobile' : '' }}
     {{ $shouldShowHeatmapSearchSidebar ? 'active' : '' }}
     {{ $showAds ? 'ad_loaded' : '' }}
         ">
    <div class="bg-header">
        <div id="heatmap_search_sidebar_trigger" class="handle">
            <i class="fas {{ $shouldShowHeatmapSearchSidebar ? 'fa-arrow-right' : 'fa-arrow-left' }}"></i>
        </div>

        <div class="p-1">
            <div class="row pr-2 mb-2 no-gutters">
                <div class="col-auto" data-toggle="tooltip"
                     title="{{ __('view_common.maps.controls.heatmapsearch.settings_title') }}">
                    <button class="btn btn-info w-100" data-toggle="modal" data-target="#map_settings_modal">
                        <i class='fas fa-cog'></i>
                    </button>
                </div>
                <div class="col pl-2 pr-2">
                    <div id="heatmap_search_loader" class="w-100 text-center">
                        <h5 class="pt-1">
                            <i class="fas fa-stroopwafel fa-spin"></i> {{ __('view_common.maps.controls.heatmapsearch.loading') }}
                        </h5>
                    </div>
                </div>
                <div class="col-auto">
                    <input id="heatmap_search_toggle" type="checkbox"
                           {{ $heatmapSearchEnabled ? 'checked' : '' }}
                           data-toggle="toggle" data-width="100px" data-height="20px"
                           data-onstyle="primary" data-offstyle="primary"
                           data-on="{{ __('view_common.maps.controls.heatmapsearch.enabled') }}"
                           data-off="{{ __('view_common.maps.controls.heatmapsearch.disabled') }}">
                </div>
            </div>
        </div>

        <div class="data_container p-2" data-simplebar>
            <div id="heatmap_search_options_container">
                <div class="row">
                    <div class="col">
                        <div id="heatmap_search_options_current_filters" class="pl-1">

                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div id="filter_event_type_container" class="btn-group btn-group-toggle w-100"
                         data-toggle="buttons">
                        <label class="btn btn-secondary active">
                            <input type="radio" name="event_type"
                                   class="{{ CombatLogEvent::EVENT_TYPE_ENEMY_KILLED }}"
                                   value="{{ CombatLogEvent::EVENT_TYPE_ENEMY_KILLED }}"
                                   checked>
                            <i class="fas fa-users"></i> {{ __('combatlogeventtypes.enemy_killed') }}
                        </label>
                        <label class="btn btn-secondary">
                            <input type="radio" name="event_type"
                                   class="{{ CombatLogEvent::EVENT_TYPE_PLAYER_DEATH }}"
                                   value="{{ CombatLogEvent::EVENT_TYPE_PLAYER_DEATH }}">
                            <i class="fas fa-skull-crossbones"></i> {{ __('combatlogeventtypes.player_death') }}
                        </label>
                    </div>
                </div>

                @component('common.search.filter', [
                    'key' => 'data_type',
                    'text' => __('view_common.maps.controls.heatmapsearch.data_type'),
                    'expanded' => $expandedDataType,
                    'title' => __('view_common.maps.controls.heatmapsearch.data_type_title'),
                ])
                    <div id="filter_data_type_container" class="btn-group btn-group-toggle w-100 mb-1"
                         data-toggle="buttons">
                        <label class="btn btn-secondary">
                            <input type="radio" name="data_type"
                                   class="{{ CombatLogEvent::DATA_TYPE_ENEMY_POSITION }}"
                                   value="{{ CombatLogEvent::DATA_TYPE_ENEMY_POSITION }}">
                            <i class="fas fa-map-marked-alt"></i> {{ __('combatlogdatatypes.enemy_position') }}
                        </label>
                        <label class="btn btn-secondary active">
                            <input type="radio" name="data_type"
                                   class="{{ CombatLogEvent::DATA_TYPE_PLAYER_POSITION }}"
                                   value="{{ CombatLogEvent::DATA_TYPE_PLAYER_POSITION }}"
                                   checked>
                            <i class="fas fa-map"></i> {{ __('combatlogdatatypes.player_position') }}
                        </label>
                    </div>
                @endcomponent

                @component('common.search.filter', ['key' => 'level', 'text' => __('view_common.maps.controls.heatmapsearch.key_level'), 'expanded' => $expandedKeyLevel])
                    <input id="filter_level" type="text" name="level" value="{{ old('level') }}"/>
                @endcomponent

                @if($dungeon->gameVersion->has_seasons)
                    @component('common.search.filter', ['key' => 'affixes', 'text' => __('view_common.maps.controls.heatmapsearch.affixes'), 'expanded' => $expandedAffixes])
                        <div class="filter_affix">
                            <div class="row">
                                <div class="col">
                                    {!! Form::select('filter_affixes[]', $affixGroups->pluck('text', 'id'), [],
                                        ['id' => 'filter_affixes',
                                        'class' => 'form-control affixselect selectpicker',
                                        'multiple' => 'multiple',
                                        'title' => __('view_common.maps.controls.heatmapsearch.affixes_title'),
                                        'data-selected-text-format' => 'count > 1',
                                        'data-count-selected-text' => __('view_common.maps.controls.heatmapsearch.affixes_selected')]) !!}
                                </div>
                            </div>
                                <?php
                                $chunkedFeaturedAffixes = $featuredAffixes->chunk($featuredAffixes->count() < 9 ? 4 : (int)($featuredAffixes->count() / 2));
                                ?>
                            @foreach($chunkedFeaturedAffixes as $affixRow)
                                <div class="row mt-2 pl-2 featured_affixes">
                                    @foreach($affixRow as $affix)
                                            <?php /** @var $affix Affix */ ?>
                                        <div class="col px-xl-1">
                                            <div
                                                    class="select_icon class_icon affix_icon_{{ $affix->image_name }} selectable"
                                                    data-toggle="tooltip" data-id="{{ $affix->id }}"
                                                    title="{{ __($affix->description) }}"
                                                    style="height: 24px;">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endcomponent
                @endif

                @component('common.search.filter', ['key' => 'weekly_affix_groups', 'text' => __('view_common.maps.controls.heatmapsearch.weekly_affix_groups'), 'expanded' => $expandedAffixWeek])
                    <div class="filter_affix">
                        <div class="row">
                            <div class="col">
                                {!! Form::select('filter_weekly_affix_groups[]',
                                    $seasonWeeklyAffixGroups->mapWithKeys(function(WeeklyAffixGroup $seasonWeeklyAffixGroup){
                                        return [$seasonWeeklyAffixGroup->week => $seasonWeeklyAffixGroup->affixGroup->text];
                                    }), [],
                                    ['id' => 'filter_weekly_affix_groups',
                                    'class' => 'form-control affixselect selectpicker',
                                    'title' => __('view_common.maps.controls.heatmapsearch.weekly_affix_groups_title')]) !!}
                            </div>
                        </div>
                    </div>
                @endcomponent

                @component('common.search.filter', ['key' => 'duration', 'text' => __('view_common.maps.controls.heatmapsearch.duration'), 'expanded' => $expandedDuration])
                    <input id="filter_duration" type="text" name="duration" value="{{ old('duration') }}"/>
                @endcomponent

                <div class="row">
                    <div id="heatmap_search_result" class="col" style="visibility: hidden;">
                        <div class="pl-1">
                            {!! __('view_common.maps.controls.heatmapsearch.data.dungeon_routes', [
                                'count' => '<span id="heatmap_search_result_data_dungeonroutes"> </span>',
                            ]) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</nav>
