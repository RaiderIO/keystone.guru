<?php

use App\Logic\MapContext\MapContext;
use App\Logic\MapContext\MapContextDungeonExplore;
use App\Logic\MapContext\MapContextDungeonRoute;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\LiveSession;
use App\Models\Mapping\MappingVersion;
use App\Models\User;

/**
 * @var User              $user
 * @var MapContext        $mapContext
 * @var Dungeon           $dungeon
 * @var Floor             $floor
 * @var MappingVersion    $mappingVersion
 * @var DungeonRoute|null $dungeonroute
 * @var LiveSession|null  $livesession
 * @var bool|null         $admin
 * @var bool|null         $embed
 * @var string|null       $embedStyle
 * @var bool|null         $edit
 * @var array             $show
 * @var bool              $adFree
 * @var string|null       $mapBackgroundColor
 * @var string|null       $mapFacadeStyle
 */

$user               = Auth::user();
$isAdmin            = isset($admin) && $admin;
$embed              = isset($embed) && $embed;
$embedStyle         ??= '';
$edit               = isset($edit) && $edit;
$mapClasses         ??= '';
$dungeonroute       ??= null;
$livesession        ??= null;
$mapBackgroundColor ??= null;

// Ensure default values for showing/hiding certain elements
$show['controls']                  ??= [];
$show['controls']['enemyInfo']     ??= true;
$show['controls']['pulls']         ??= true;
$show['controls']['heatmapSearch'] ??= false;
$show['controls']['enemyForces']   = $show['controls']['pulls'] && ($show['controls']['enemyForces'] ?? true);
$show['controls']['draw']          ??= false;
$show['controls']['view']          ??= false;
$show['controls']['present']       ??= false;
$show['controls']['live']          ??= false;

// Set the key to 'sandbox' if sandbox mode is enabled
$sandboxMode                      = isset($sandboxMode) && $sandboxMode;
$enemyVisualType                  = $_COOKIE['enemy_display_type'] ?? 'enemy_portrait';
$unkilledEnemyOpacity             = $_COOKIE['map_unkilled_enemy_opacity'] ?? '50';
$unkilledImportantEnemyOpacity    = $_COOKIE['map_unkilled_important_enemy_opacity'] ?? '80';
$defaultEnemyAggressivenessBorder = (int)($_COOKIE['map_enemy_aggressiveness_border'] ?? 0);
$mapFacadeStyle                   ??= User::getCurrentUserMapFacadeStyle();
$useFacade                        = $mapFacadeStyle === User::MAP_FACADE_STYLE_FACADE;

// Allow echo to be overridden
$echo           ??= Auth::check() && !$sandboxMode;
$zoomToContents ??= false;

// Show ads or not
$showAds ??= true;
// If this is an embedded route, do not show ads
if ($embed || $dungeonroute?->demo === 1) {
    $showAds = false;
}

// No UI on the map
$noUI            = isset($noUI) && $noUI;
$gestureHandling = isset($gestureHandling) && $gestureHandling;
// Default zoom for the map
$defaultZoom ??= 2;
// By default hidden elements
$hiddenMapObjectGroups ??= [];
// Show the attribution
$showAttribution = isset($showAttribution) && !$showAttribution ? false : true;

// Additional options to pass to the map when we're in an admin environment
$adminOptions = [];
if ($isAdmin) {
    $adminOptions = [
        // Display options for changing Teeming status for map objects
        'teemingOptions' => [
            ['key' => '', 'description' => __('view_common.maps.map.no_teeming')],
            ['key' => \App\Models\Enemy::TEEMING_VISIBLE, 'description' => __('view_common.maps.map.visible_teeming')],
            ['key' => \App\Models\Enemy::TEEMING_HIDDEN, 'description' => __('view_common.maps.map.hidden_teeming')],
        ],
        // Display options for changing Faction status for map objects
        'factions'       => [
            ['key' => 'any', 'description' => __('view_common.maps.map.any')],
            ['key' => \App\Models\Faction::FACTION_ALLIANCE, 'description' => __('factions.alliance')],
            ['key' => \App\Models\Faction::FACTION_HORDE, 'description' => __('factions.horde')],
        ],
    ];
}
?>
@include('common.general.inline', ['path' => 'common/maps/map', 'options' => array_merge([
    'embed' => $embed,
    'edit' => $edit,
    'readonly' => false, // May be set to true in the code though - but set a default here
    'sandbox' => $sandboxMode,
    'defaultEnemyVisualType' => $enemyVisualType,
    'defaultUnkilledEnemyOpacity' => $unkilledEnemyOpacity,
    'defaultUnkilledImportantEnemyOpacity' => $unkilledImportantEnemyOpacity,
    'defaultEnemyAggressivenessBorder' => $defaultEnemyAggressivenessBorder,
    'mapFacadeStyle' => $mapFacadeStyle,
    'noUI' => $noUI,
    'showControls' => $show['controls'],
    'gestureHandling' => $gestureHandling,
    'zoomToContents' => $zoomToContents,
    'hiddenMapObjectGroups' => $hiddenMapObjectGroups,
    'defaultZoom' => $defaultZoom,
    'showAttribution' => $showAttribution,
    'dungeonroute' => $dungeonroute ?? null,
], $adminOptions)])

@section('scripts')
    {{-- Make sure we don't override the scripts of the page this thing is included in --}}
    @parent

    @include('common.handlebars.groupsetup')

    @include('common.general.statemanager', [
        'echo' => $echo,
        'patreonBenefits' => $user?->getPatreonBenefits() ?? collect(),
        'userData' => $user?->makeVisible('map_facade_style'),
        'mapContext' => $mapContext->getProperties(),
    ])
    <script>
        var dungeonMap;

        $(function () {
            let code = _inlineManager.getInlineCode('common/maps/map');

            // Expose the dungeon map in a global variable
            dungeonMap = code.getDungeonMap();
        });
    </script>

    @if($dungeon->isFactionSelectionRequired())
        <script id="map_faction_display_controls_template" type="text/x-handlebars-template">
            <div id="map_faction_display_controls" class="leaflet-draw-section">
                <div class="leaflet-draw-toolbar leaflet-bar leaflet-draw-toolbar-top">
            @foreach(\App\Models\Faction::where('key', '<>', \App\Models\Faction::FACTION_UNSPECIFIED)->get() as $faction)
                <a class="map_faction_display_control map_controls_custom" href="#"
                   data-faction="{{ strtolower($faction->key) }}"
                           title="{{ __($faction->name) }}">
                            <i class="{{ $loop->index === 0 ? 'fas' : 'far' }} fa-circle radiobutton"
                               style="width: 15px"></i>
                            <img src="{{ $faction->iconfile->icon_url }}" class="select_icon faction_icon"
                                 data-toggle="tooltip" title="{{ __($faction->name) }}"
								 alt="Faction"/>
                        </a>

            @endforeach
            </div>
            <ul class="leaflet-draw-actions"></ul>
        </div>


        </script>
    @endif
@endsection

@if(!$noUI)
    @if(isset($show['header']) && $show['header'])
        @include('common.maps.controls.header', [
            'echo' => $echo,
            'edit' => $edit,
            'mapContext' => $mapContext,
            'dungeon' => $dungeon,
            'floor' => $floor,
            'dungeonroute' => $dungeonroute,
            'livesession' => $livesession,
            'mappingVersion' => $mappingVersion,
        ])
    @endif

    @if(isset($show['controls']['draw']) && $show['controls']['draw'])
        @include('common.maps.controls.draw', [
            'isAdmin' => $isAdmin,
            'floors' => ($isAdmin ? $dungeon->floors() : $dungeon->floorsForMapFacade($mappingVersion, $useFacade)->active())->get(),
            'selectedFloorId' => $floor->id,
            'isMobile' => $isMobile,
        ])
    @elseif(isset($show['controls']['view']) && $show['controls']['view'])
        @include('common.maps.controls.view', [
            'isAdmin' => $isAdmin,
            'floors' => ($isAdmin ? $dungeon->floors() : $dungeon->floorsForMapFacade($mappingVersion, $useFacade)->active())->get(),
            'selectedFloorId' => $floor->id,
            'dungeonroute' => $dungeonroute,
            'isMobile' => $isMobile,
        ])
    @elseif(isset($show['controls']['present']) && $show['controls']['present'])
        @include('common.maps.controls.present', [
            'isAdmin' => $isAdmin,
            'floors' => ($isAdmin ? $dungeon->floors() : $dungeon->floorsForMapFacade($mappingVersion, $useFacade)->active())->get(),
            'selectedFloorId' => $floor->id,
            'dungeonroute' => $dungeonroute,
            'isMobile' => $isMobile,
        ])
    @endif

    @if(isset($show['controls']['pulls']) && $show['controls']['pulls'])
        @include('common.maps.controls.pulls', [
            'showAds' => $showAds && !$adFree,
            'edit' => $edit,
            'defaultState' => $show['controls']['pullsDefaultState'] ?? null,
            'hideOnMove' => $show['controls']['pullsHideOnMove'] ?? null,
            'embed' => $embed,
            'embedStyle' => $embedStyle,
            'dungeonroute' => $dungeonroute,
        ])
    @endif

    @if(isset($show['controls']['heatmapSearch']) && $show['controls']['heatmapSearch'])
        @include('common.maps.controls.heatmapsearch', [
            'showAds' => $showAds && !$adFree,
            'defaultState' => $show['controls']['pullsDefaultState'] ?? null,
            'hideOnMove' => $show['controls']['pullsHideOnMove'] ?? null,
        ])
    @endif

    @if(isset($show['controls']['enemyInfo']) && $show['controls']['enemyInfo'])
        @include('common.maps.controls.enemyinfo')
    @endif

    @if(isset($show['controls']['raiderioKsgAttribution']) && $show['controls']['raiderioKsgAttribution'])
        @include('common.maps.controls.attribution')
    @endif
@endif


<div id="map" class="virtual-tour-element {{$mapClasses}}" data-position="auto"
     style="background-color: {{ $mapBackgroundColor === null ? 'inherit' : $mapBackgroundColor }}">

</div>
@if(!$noUI)

    {{--    @if(!$adFree && $showAds)--}}
    {{--        @if($isMobile)--}}
    {{--            @include('common.thirdparty.adunit', ['id' => 'map_footer', 'type' => 'footer'])--}}
    {{--        @endif--}}
    {{--    @endif--}}
    <footer class="fixed-bottom container p-0" style="width: 728px">
        <div id="snackbar_container">

        </div>
        {{--        @if(!$adFree && $showAds)--}}
        {{--            @include('common.thirdparty.adunit', ['id' => 'map_footer', 'type' => 'footer', 'class' => 'map_ad_background', 'map' => true])--}}
        {{--        @endif--}}
    </footer>

        <?php
        /*
        So speedrun dungeons are such low traffic that this doesn't really matter anyways. But those routes already
        have to fight for height in the sidebar. This will only make it worse, so don't render this ad
        */ ?>
    {{--    @if(!$adFree && $showAds)--}}
    {{--        @if( $mapContext instanceof \App\Logic\MapContext\MapContextDungeonExplore )--}}
    {{--            <footer class="fixed-bottom container p-0 m-0 map_ad_unit_sidebar_right">--}}
    {{--                @include('common.thirdparty.adunit', ['id' => 'map_sidebar_right', 'type' => 'sidebar_map_right', 'class' => 'map_ad_background', 'map' => true])--}}
    {{--            </footer>--}}
    {{--        @elseif(!$dungeon->speedrun_enabled)--}}
    {{--            <footer class="fixed-bottom container p-0 m-0 mr-2 map_ad_unit_footer_right">--}}
    {{--                @include('common.thirdparty.adunit', ['id' => 'map_footer_right', 'type' => 'footer_map_right', 'class' => 'map_ad_background', 'map' => true])--}}
    {{--            </footer>--}}
    {{--        @endif--}}
    {{--    @endif--}}



    @if($mapContext instanceof MapContextDungeonRoute || $mapContext instanceof MapContextDungeonExplore)
        @component('common.general.modal', ['id' => 'userreport_dungeonroute_modal'])
            @include('common.modal.userreport.dungeonroute', ['dungeonroute' => $dungeonroute])
        @endcomponent

        @component('common.general.modal', ['id' => 'userreport_enemy_modal'])
            @include('common.modal.userreport.enemy')
        @endcomponent
    @endisset

    @if(isset($show['controls']['pulls']) && $show['controls']['pulls'] ||
        isset($show['controls']['heatmapSearch']) && $show['controls']['heatmapSearch'])
        @component('common.general.modal', ['id' => 'map_settings_modal', 'size' => 'xl'])
            @include('common.modal.mapsettings', ['dungeonroute' => $dungeonroute, 'edit' => $edit])
        @endcomponent
    @endif
@endif
