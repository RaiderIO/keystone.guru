<?php
/**
 * @var bool                                  $showAds
 * @var bool                                  $edit
 * @var \App\Models\DungeonRoute\DungeonRoute $model
 * @var \App\Models\Dungeon                   $dungeon
 * @var bool                                  $embed
 * @var string                                $embedStyle
 * @var bool                                  $isMobile
 * @var integer                               $defaultState
 * @var bool                                  $hideOnMove
 * @var bool                                  $showAllEnabled
 */
// By default, show it if we're not mobile, but allow overrides
$pullsSidebarState              = (int)($_COOKIE['pulls_sidebar_state'] ?? 1);
$defaultState                   ??= $isMobile ? 0 : $pullsSidebarState;
$shouldShowHeatmapSearchSidebar = $defaultState === 1;
$hideOnMove                     ??= $isMobile;
$showAds                        ??= true;
?>
@include('common.general.inline', ['path' => 'common/maps/heatmapsearchsidebar', 'options' => [
    'stateCookie' => 'heatmap_search_sidebar_state',
    'defaultState' => $defaultState,
    'hideOnMove' => $hideOnMove,
    'dependencies' => ['common/maps/map'],
    // Mobile sidebar options
    'sidebarSelector' => '#heatmap_search_sidebar',
    'sidebarToggleSelector' => '#heatmap_search_sidebar_trigger',
    'sidebarScrollSelector' => '#heatmap_search_sidebar .heatmap_search_container',
    'anchor' => 'right',
    'edit' => $edit,
]])

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

        <div class="heatmap_search_container" data-simplebar>
            <div id="heatmap_search_options_container">
                <h5>{{ __('view_common.maps.controls.pulls.loading') }}</h5>
            </div>
        </div>

    </div>
</nav>
