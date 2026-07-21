<?php

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\GameServerRegion;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\Season;
use App\Service\Season\Dtos\WeeklyAffixGroup;
use Illuminate\Support\Collection;

/**
 * @var bool|null      $showAds
 * @var MappingVersion $mappingVersion
 * @var bool           $isMobile
 * @var int            $keyLevelMin
 * @var int            $keyLevelMax
 */

// By default, show it if we're not mobile, but allow overrides
$dungeonRouteSearchSidebarState = (int)($_COOKIE['dungeonroute_search_sidebar_state'] ?? 1);
$defaultState                   ??= $isMobile ? 0 : $dungeonRouteSearchSidebarState;
$dungeonRouteSearchEnabled      = (bool)($_COOKIE['dungeonroute_search_enabled'] ?? 1);
$filterExpandedCookiePrefix     = 'dungeonroute_search_expanded';

$isDungeonRouteSearchSidebarDefaultVisible = $defaultState === 1;
$hideOnMove                                ??= $isMobile;
$showAds                                   ??= true;
?>
@include('common.general.inline', ['path' => 'common/maps/dungeonroutesearchsidebar', 'options' => [
    'stateCookie' => 'dungeonroute_search_sidebar_state',
    'defaultState' => $defaultState,
    'hideOnMove' => $hideOnMove,
    'currentFiltersSelector' => '#dungeonroute_search_options_current_filters',
    'loaderSelector' => '#dungeonroute_search_loader',

    'gameVersion' => $mappingVersion->gameVersion->key,
    'dungeon' => $mappingVersion->dungeon->slug,

    'keyLevelMin' => $keyLevelMin,
    'keyLevelMax' => $keyLevelMax,

    'enabledStateCookie' => 'dungeonroute_search_enabled',
    'enabledStateSelector' => '#dungeonroute_search_toggle',
    'filterKeyLevelSelector' => '#filter_key_level',
    'filterTitleSelector' => '#filter_title',
    'filterUsernameSelector' => '#filter_username',

    'filterCollapseNames' => ['keyLevel',],
    'filterCookiePrefix' => $filterExpandedCookiePrefix,

    'dependencies' => ['common/maps/map'],
    // Mobile sidebar options
    'sidebarSelector' => '#dungeonroute_search_sidebar',
    'sidebarToggleSelector' => '#dungeonroute_search_sidebar_trigger',
    'sidebarScrollSelector' => '#dungeonroute_search_sidebar .data_container',
    'sidebarSearchResultSelector' => '#dungeonroute_search_routes_container',
    'anchor' => 'right',
    'edit' => $edit,
]])

<!--suppress HtmlFormInputWithoutLabel -->
<nav id="dungeonroute_search_sidebar"
     class="route_sidebar top right row g-0 map_fade_out
     {{ $embed ? 'embed' : '' }}
     {{ $embedStyle }}
     {{ $isMobile ? 'mobile' : '' }}
     {{ $isDungeonRouteSearchSidebarDefaultVisible ? 'active' : '' }}
     {{ $showAds ? 'ad_loaded' : '' }}
         ">
    <div class="bg-header">
        @if($showSidebar)
            <div id="dungeonroute_search_sidebar_trigger" class="handle" data-bs-toggle="tooltip">
                <i class="fas {{ $isDungeonRouteSearchSidebarDefaultVisible ? 'fa-arrow-right' : 'fa-arrow-left' }}"></i>
            </div>
        @endif

        <div class="p-1">
            <div class="row pe-2 mb-2 g-0">
                <div class="col-auto" data-bs-toggle="tooltip"
                     title="{{ __('view_common.maps.controls.dungeonroutesearch.settings_title') }}">
                    <button class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#map_settings_modal">
                        <i class='fas fa-cog'></i>
                    </button>
                </div>
                <div class="col px-2 text-center">
                    {{ __('view_common.maps.controls.dungeonroutesearch.description') }}
                </div>
                <div class="col-auto">
                    <input id="dungeonroute_search_toggle" type="checkbox"
                           {{ $dungeonRouteSearchEnabled ? 'checked' : '' }}
                           data-toggle="toggle" data-width="100px" data-height="20px"
                           data-onstyle="primary" data-offstyle="primary"
                           data-on="{{ __('view_common.maps.controls.dungeonroutesearch.enabled') }}"
                           data-off="{{ __('view_common.maps.controls.dungeonroutesearch.disabled') }}">
                </div>
            </div>

            <div class="row px-2 pt-2 pb-0">
                <div class="col">
                    <div id="dungeonroute_search_options_current_filters">

                    </div>
                </div>
            </div>
        </div>

        <div class="data_container explore p-2" data-simplebar>
            <div id="dungeonroute_search_options_container" class="px-1">
                @component('common.forms.labelinput', [
                    'name' => 'key_level',
                    'label' => __('view_common.maps.controls.dungeonroutesearch.key_level')
                ])
                    <input id="filter_key_level" type="text" name="key_level" value="{{ old('key_level') }}"/>
                @endcomponent

                @component('common.forms.labelinput', [
                    'name' => 'title',
                    'label' => __('view_common.maps.controls.dungeonroutesearch.title')
                ])
                    <input id="filter_title" type="text" class="form-control" name="title" value="{{ old('title') }}"/>
                @endcomponent

                @component('common.forms.labelinput', [
                    'name' => 'username',
                    'label' => __('view_common.maps.controls.dungeonroutesearch.username')
                ])
                    <input id="filter_username" type="text" class="form-control" name="username" value="{{ old('username') }}"/>
                @endcomponent

                <div id="dungeonroute_search_routes_container" class="mb-3">

                </div>
            </div>
        </div>

    </div>
</nav>
