<?php

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * @var bool                   $showAds
 * @var Dungeon                $dungeon
 * @var bool                   $embed
 * @var string                 $embedStyle
 * @var bool                   $isMobile
 * @var integer                $defaultState
 * @var bool                   $hideOnMove
 * @var bool                   $showAllEnabled
 * @var Collection<AffixGroup> $allAffixGroupsByActiveExpansion
 * @var Collection<Affix>      $featuredAffixesByActiveExpansion
 * @var CarbonPeriod           $availableDateRange
 */

// By default, show it if we're not mobile, but allow overrides
$pullsSidebarState              = (int)($_COOKIE['pulls_sidebar_state'] ?? 1);
$defaultState                   ??= $isMobile ? 0 : $pullsSidebarState;
$shouldShowHeatmapSearchSidebar = $defaultState === 1;
$hideOnMove                     ??= $isMobile;
$showAds                        ??= true;
/** @var $affixGroups Collection<AffixGroup> */
$affixGroups = $allAffixGroupsByActiveExpansion->get($dungeon->expansion->shortname);
/** @var $featuredAffixes Collection<Affix> */
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

    'levelMin' => config('keystoneguru.keystone.levels.min'),
    'levelMax' => config('keystoneguru.keystone.levels.max'),
    'durationMin' => 5,
    'durationMax' => 60,

    'filterLevelSelector' => '#filter_level',
    'filterAffixGroupsSelector' => '#filter_affixes',
    'filterAffixesSelector' => '.select_icon.class_icon.selectable',
    'filterDateRangeFromSelector' => '#filter_date_from',
    'filterDateRangeToSelector' => '#filter_date_to',
    'filterDateRangeFromClearBtnSelector' => '#filter_date_from_clear_btn',
    'filterDateRangeToClearBtnSelector' => '#filter_date_to_clear_btn',
    'filterDurationSelector' => '#filter_duration',

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
            </div>
        </div>

        <div class="data_container p-2" data-simplebar>
            <div id="heatmap_search_options_container">
                <div class="row mb-2">
                    <div class="col">
                        <div id="heatmap_search_options_current_filters" class="pl-1">

                        </div>
                    </div>
                </div>

                @component('common.search.filter', ['key' => 'level', 'text' => __('view_common.maps.controls.heatmapsearch.key_level')])
                    <input id="filter_level" type="text" name="level" value="{{ old('level') }}"/>
                @endcomponent

                @if($dungeon->gameVersion->has_seasons)
                    @component('common.search.filter', ['key' => 'affixes', 'text' => __('view_common.maps.controls.heatmapsearch.affixes')])
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
                                $chunkedFeaturedAffixes = $featuredAffixes->chunk($featuredAffixes->count() < 9 ? 4 : 5);
                                ?>
                            @foreach($chunkedFeaturedAffixes as $affixRow)
                                <div class="row mt-2 pl-2 featured_affixes">
                                    @foreach($affixRow as $affix)
                                            <?php /** @var $affix Affix */ ?>
                                        <div class="col px-xl-1">
                                            <div
                                                class="select_icon class_icon affix_icon_{{ strtolower($affix->key) }} selectable"
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

                @component('common.search.filter', ['key' => 'date_range', 'text' => __('view_common.maps.controls.heatmapsearch.date_range')])
                    <div class="row">
                        <div class="col">
                            <div class="row no-gutters">
                                <div class="col">
                                    <label for="date_range_from">
                                        {{ __('view_common.maps.controls.heatmapsearch.date_range_from') }}
                                    </label>
                                </div>
                                <div class="col">
                                    <input id="filter_date_from" type="date" name="date_range_from"
                                           value="{{ old('date_range_from') }}" style="width: 115px"
                                           min="{{ $availableDateRange->start->toDateString() }}"
                                           max="{{ $availableDateRange->end->toDateString() }}"/>
                                </div>
                                <div class="col">
                                    <div id="filter_date_from_clear_btn" class="btn btn-sm text-danger">
                                        <i class="fas fa-times"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="row no-gutters">
                                <div class="col">
                                    <label for="date_range_to">
                                        {{ __('view_common.maps.controls.heatmapsearch.date_range_to') }}
                                    </label>
                                </div>
                                <div class="col">
                                    <input id="filter_date_to" type="date" name="date_range_to"
                                           value="{{ old('date_range_to') }}" style="width: 115px"
                                           min="{{ $availableDateRange->start->toDateString() }}"
                                           max="{{ $availableDateRange->end->toDateString() }}">
                                </div>
                                <div class="col">
                                    <div id="filter_date_to_clear_btn" class="btn btn-sm text-danger">
                                        <i class="fas fa-times"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endcomponent

                @component('common.search.filter', ['key' => 'duration', 'text' => __('view_common.maps.controls.heatmapsearch.duration')])
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
