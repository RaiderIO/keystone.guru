<?php

use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\GameServerRegion;
use App\Models\Laratrust\Role;
use App\Service\Season\Dtos\SeasonWeek;
use Illuminate\Support\Collection;

/**
 * The heatmap sidebar of a continent: the open-world filter set, without anything Mythic+ specific.
 *
 * @var bool                                  $showAds
 * @var bool                                  $showSidebar
 * @var bool|null                             $showDataSourceSnackbar
 * @var Dungeon                               $dungeon
 * @var bool                                  $embed
 * @var string                                $embedStyle
 * @var bool                                  $isMobile
 * @var integer|null                          $defaultState
 * @var bool                                  $hideOnMove
 * @var int                                   $itemLevelMin
 * @var int                                   $itemLevelMax
 * @var int                                   $minSamplesRequiredMin
 * @var int                                   $minSamplesRequiredMax
 * @var Collection<int, SeasonWeek>           $continentWeeks
 * @var Collection<int, GameServerRegion>     $allRegions
 */

// By default, show it if we're not mobile, but allow overrides
$heatmapSearchSidebarState  = (int)($_COOKIE['heatmap_search_sidebar_state'] ?? 1);
$defaultState               ??= $isMobile ? 0 : $heatmapSearchSidebarState;
$heatmapSearchEnabled       = (bool)($_COOKIE['heatmap_search_enabled'] ?? 1);
$filterExpandedCookiePrefix = 'heatmap_search_expanded';

$isHeatmapSearchSidebarDefaultVisible = $defaultState === 1;
$hideOnMove                           ??= $isMobile;
$showAds                              ??= true;

$allRegions = $allRegions->sort(function (GameServerRegion $a, GameServerRegion $b) {
    // If one of them is "World", it comes first
    if ($a->key === GameServerRegion::WORLD) return -1;
    if ($b->key === GameServerRegion::WORLD) return 1;

    // Otherwise, sort by ID ascending
    return $a->id <=> $b->id;
});

?>
@include('common.general.inline', ['path' => 'common/maps/continentheatmapsearchsidebar', 'options' => [
    'stateCookie' => 'heatmap_search_sidebar_state',
    'defaultState' => $defaultState,
    'hideOnMove' => $hideOnMove,
    'currentFiltersSelector' => '#heatmap_search_options_current_filters',
    'loaderSelector' => '#heatmap_search_loader',

    // If the sidebar is hidden from view, ignore all UI options and just show the map
    'passThroughEverything' => !$showSidebar,
    'showDataSourceSnackbar' => $showDataSourceSnackbar ?? true,

    'itemLevelMin' => $itemLevelMin,
    'itemLevelMax' => $itemLevelMax,
    'minSamplesRequiredMin' => $minSamplesRequiredMin,
    'minSamplesRequiredMax' => $minSamplesRequiredMax,

    'enabledStateCookie' => 'heatmap_search_enabled',
    'enabledStateSelector' => '#heatmap_search_toggle',
    'filterEventTypeContainerSelector' => '#filter_event_type_container',
    'filterEventTypeSelector' => 'input[name="event_type"]',
    'filterRegionContainerSelector' => '#filter_region_container',
    'filterRegionSelector' => 'input[name="region"]',
    'filterItemLevelSelector' => '#filter_item_level',
    'filterWeeksSelector' => '#filter_weeks',
    'filterMinSamplesRequiredSelector' => '#filter_min_samples_required',

    'filterCollapseNames' => [],
    'filterCookiePrefix' => $filterExpandedCookiePrefix,

    'leafletHeatOptionsMinOpacitySelector' => '#heatmap_heat_option_min_opacity',
    'leafletHeatOptionsMaxZoomSelector' => '#heatmap_heat_option_max_zoom',
    'leafletHeatOptionsMaxSelector' => '#heatmap_heat_option_max',
    'leafletHeatOptionsRadiusSelector' => '#heatmap_heat_option_radius',
    'leafletHeatOptionsBlurSelector' => '#heatmap_heat_option_blur',
    'leafletHeatOptionsGradientSelector' => '#heatmap_heat_option_gradient',
    'leafletHeatOptionsPaneSelector' => '#heatmap_heat_option_pane',

    'dependencies' => ['common/maps/map'],
    // Mobile sidebar options
    'sidebarSelector' => '#heatmap_search_sidebar',
    'sidebarToggleSelector' => '#heatmap_search_sidebar_trigger',
    'sidebarScrollSelector' => '#heatmap_search_sidebar .data_container',
    'anchor' => 'right',
    'edit' => $edit,
]])

<!--suppress HtmlFormInputWithoutLabel -->
<nav id="heatmap_search_sidebar"
     class="route_sidebar top right row g-0 map_fade_out
     {{ $embed ? 'embed' : '' }}
     {{ $embedStyle }}
     {{ $isMobile ? 'mobile' : '' }}
     {{ $isHeatmapSearchSidebarDefaultVisible ? 'active' : '' }}
     {{ $showAds ? 'ad_loaded' : '' }}
         ">
    <div class="bg-header">
        @if($showSidebar)
            <div id="heatmap_search_sidebar_trigger" class="handle" data-bs-toggle="tooltip">
                <i class="fas {{ $isHeatmapSearchSidebarDefaultVisible ? 'fa-arrow-right' : 'fa-arrow-left' }}"></i>
            </div>
        @endif

        <div class="p-1">
            <div class="row pe-2 mb-2 g-0">
                <div class="col-auto" data-bs-toggle="tooltip"
                     title="{{ __('view_common.maps.controls.continentheatmapsearch.settings_title') }}">
                    <button class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#map_settings_modal">
                        <i class='fas fa-cog'></i>
                    </button>
                </div>
                <div class="col">
                </div>
                <div class="col-auto">
                    <input id="heatmap_search_toggle" type="checkbox"
                           {{ $heatmapSearchEnabled ? 'checked' : '' }}
                           data-toggle="toggle" data-width="100px" data-height="20px"
                           data-onstyle="primary" data-offstyle="primary"
                           data-on="{{ __('view_common.maps.controls.continentheatmapsearch.enabled') }}"
                           data-off="{{ __('view_common.maps.controls.continentheatmapsearch.disabled') }}">
                </div>
            </div>

            <div class="row px-2 pt-2 pb-0">
                <div class="col">
                    <div id="heatmap_search_options_current_filters">

                    </div>
                </div>
            </div>
        </div>

        <div class="data_container explore p-2" data-simplebar>
            <div id="heatmap_search_options_container" class="px-1">

                @component('common.forms.labelinput', [
                    'name' => 'filter_death_type',
                    'label' => __('view_common.maps.controls.continentheatmapsearch.death_type'),
                ])
                    <div id="filter_event_type_container" class="btn-group w-100" role="group">
                        <input type="radio" name="event_type" id="filter_event_type_{{ CombatLogEventEventType::PlayerDeath->value }}"
                               class="btn-check {{ CombatLogEventEventType::PlayerDeath->value }}"
                               value="{{ CombatLogEventEventType::PlayerDeath->value }}"
                               checked>
                        <label class="btn btn-secondary" for="filter_event_type_{{ CombatLogEventEventType::PlayerDeath->value }}">
                            <img src="{{ ksgAssetImage('spells/ability_rogue_feigndeath.jpg') }}"
                                 alt="{{ __('view_common.maps.controls.continentheatmapsearch.player_death_alt') }}"
                                 class="filter_event_type_icon">
                            {{ __('combatlogeventtypes.player_death') }}
                        </label>
                        <input type="radio" name="event_type" id="filter_event_type_{{ CombatLogEventEventType::NpcDeath->value }}"
                               class="btn-check {{ CombatLogEventEventType::NpcDeath->value }}"
                               value="{{ CombatLogEventEventType::NpcDeath->value }}">
                        <label class="btn btn-secondary" for="filter_event_type_{{ CombatLogEventEventType::NpcDeath->value }}">
                            <img src="{{ ksgAssetImage('spells/achievement_bg_killxenemies_generalsroom.jpg') }}"
                                 alt="{{ __('view_common.maps.controls.continentheatmapsearch.npc_death_alt') }}"
                                 class="filter_event_type_icon">
                            {{ __('combatlogeventtypes.npc_death') }}
                        </label>
                    </div>
                @endcomponent

                <div class="mb-3">
                    <div id="filter_region_container" class="btn-group w-100" role="group">
                        <?php
                        $defaultRegion = GameServerRegion::WORLD;
                        ?>
                        @foreach($allRegions as $region)
                            <input type="radio" name="region" id="filter_region_{{ $region->key }}"
                                   class="btn-check {{ $region->key }}"
                                   value="{{ $region->key }}"
                                {{ $region->key === $defaultRegion ? 'checked' : '' }}
                            >
                            <label class="btn btn-secondary" for="filter_region_{{ $region->key }}">
                                <img src="{{ ksgAssetImage(sprintf('flags/%s.webp', $region->key)) }}"
                                     alt="{{ __($region->name) }}"
                                     class="filter_region_icon" loading="lazy">
                                {{ __($region->name) }}
                            </label>
                        @endforeach
                    </div>
                </div>

                @component('common.forms.labelinput', [
                    'name' => 'item_level',
                    'label' => __('view_common.maps.controls.continentheatmapsearch.item_level'),
                ])
                    <input id="filter_item_level" type="text" name="item_level" value="{{ old('item_level') }}"/>
                @endcomponent

                @component('common.forms.labelinput', [
                    'name' => 'weeks',
                    'label' => __('view_common.maps.controls.continentheatmapsearch.week'),
                ])
                    {{
                        html()
                        ->multiselect('filter_weeks[]', $continentWeeks->mapWithKeys(fn(SeasonWeek $seasonWeek) => [
                            $seasonWeek->week => __('view_common.maps.controls.continentheatmapsearch.week_option', [
                                'date' => $seasonWeek->start->format('Y-m-d'),
                            ]),
                        ]), [])
                        ->id('filter_weeks')
                        ->name('weeks')
                        ->class('form-control selectpicker')
                    }}
                @endcomponent

                @if(Auth::check() && Auth::user()->hasRole(Role::ROLE_ADMIN))
                    @component('common.search.filter', ['key' => 'advanced_search', 'text' => __('view_common.maps.controls.continentheatmapsearch.advanced_options'), 'expanded' => true])

                        @component('common.forms.labelinput', [
                            'name' => 'min_samples_required',
                            'label' => __('view_common.maps.controls.continentheatmapsearch.advanced_option.min_samples_required'),
                            'title' => __('view_common.maps.controls.continentheatmapsearch.advanced_option.min_samples_required_title'),
                        ])
                            <input id="filter_min_samples_required" type="text" name="filter_min_samples_required"
                                   value="50"/>
                        @endcomponent

                    @endcomponent

                    @component('common.search.filter', ['key' => 'heatoptions', 'text' => __('view_common.maps.controls.continentheatmapsearch.heat_options'), 'expanded' => true])
                        <div class="row">
                            <div class="col">
                                <label for="heatmap_heat_option_min_opacity">
                                    {{ __('view_common.maps.controls.continentheatmapsearch.heat_option.min_opacity') }}
                                </label>
                            </div>
                            <div class="col">
                                <input id="heatmap_heat_option_min_opacity" type="text" name="min_opacity" value="0.1"/>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <label for="heatmap_heat_option_max_zoom">
                                    {{ __('view_common.maps.controls.continentheatmapsearch.heat_option.max_zoom') }}
                                </label>
                            </div>
                            <div class="col">
                                <input id="heatmap_heat_option_max_zoom" type="text" name="max_zoom" value="5"/>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <label for="heatmap_heat_option_max">
                                    {{ __('view_common.maps.controls.continentheatmapsearch.heat_option.max') }}
                                </label>
                            </div>
                            <div class="col">
                                <input id="heatmap_heat_option_max" type="text" name="max" value="1.0"/>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <label for="heatmap_heat_option_radius">
                                    {{ __('view_common.maps.controls.continentheatmapsearch.heat_option.radius') }}
                                </label>
                            </div>
                            <div class="col">
                                <input id="heatmap_heat_option_radius" type="text" name="radius" value="35"/>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <label for="heatmap_heat_option_blur">
                                    {{ __('view_common.maps.controls.continentheatmapsearch.heat_option.blur') }}
                                </label>
                            </div>
                            <div class="col">
                                <input id="heatmap_heat_option_blur" type="text" name="blur" value="15"/>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <label for="heatmap_heat_option_gradient">
                                    {{ __('view_common.maps.controls.continentheatmapsearch.heat_option.gradient') }}
                                </label>
                            </div>
                            <div class="col">
                                <input id="heatmap_heat_option_gradient" type="text" name="gradient"
                                       value='{".4":"blue",".6":"cyan",".7":"lime",".8":"yellow","1":"red"}'/>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <label for="heatmap_heat_option_pane">
                                    {{ __('view_common.maps.controls.continentheatmapsearch.heat_option.pane') }}
                                </label>
                            </div>
                            <div class="col">
                                {{
                                    html()
                                        ->select('pane', ['overlayPane' => 'Overlay', 'markerPane' => 'Marker', 'tooltipPane' => 'Tooltip'], 'overlayPane')
                                        ->id('heatmap_heat_option_pane')
                                        ->class('selectpicker')
                                }}
                            </div>
                        </div>
                    @endcomponent
                @endif
            </div>
        </div>

    </div>
</nav>
