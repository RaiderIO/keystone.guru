<?php
/** @var \App\User $user */
/** @var \App\Logic\MapContext\MapContext $mapContext */
/** @var App\Models\Dungeon $dungeon */
/** @var App\Models\DungeonRoute|null $dungeonroute */
/** @var array $show */
/** @var bool $adFree */

$user = Auth::user();
$isAdmin = isset($admin) && $admin;
$embed = isset($embed) && $embed;
$edit = isset($edit) && $edit;
$mapClasses = $mapClasses ?? '';
$dungeonroute = $dungeonroute ?? null;

// Set the key to 'sandbox' if sandbox mode is enabled
$sandboxMode = isset($sandboxMode) && $sandboxMode;
$enemyVisualType = $_COOKIE['enemy_display_type'] ?? 'enemy_portrait';
$unkilledEnemyOpacity = $_COOKIE['map_unkilled_enemy_opacity'] ?? '50';
$unkilledImportantEnemyOpacity = $_COOKIE['map_unkilled_important_enemy_opacity'] ?? '80';
$defaultEnemyAggressivenessBorder = (int)($_COOKIE['map_enemy_aggressiveness_border'] ?? 0);

// Allow echo to be overridden
$echo = $echo ?? Auth::check() && !$sandboxMode;
$zoomToContents = $zoomToContents ?? false;

// Show ads or not
$showAds = $showAds ?? true;
// If this is an embedded route, do not show ads
if ($embed || optional($dungeonroute)->demo === 1) {
    $showAds = false;
}
// No UI on the map
$noUI = isset($noUI) && $noUI;
$gestureHandling = isset($gestureHandling) && $gestureHandling;
// Default zoom for the map
$defaultZoom = $defaultZoom ?? 2;
// By default hidden elements
$hiddenMapObjectGroups = $hiddenMapObjectGroups ?? [];
// Show the attribution
$showAttribution = isset($showAttribution) && !$showAttribution ? false : true;

// Additional options to pass to the map when we're in an admin environment
$adminOptions = [];
if ($isAdmin) {
    $adminOptions = [
        // Display options for changing Teeming status for map objects
        'teemingOptions' => [
            ['key' => '', 'description' => __('Always visible')],
            ['key' => 'visible', 'description' => __('Visible when Teeming only')],
            ['key' => 'hidden', 'description' => __('Hidden when Teeming only')],
        ],
        // Display options for changing Faction status for map objects
        'factions' => [
            ['key' => 'any', 'description' => __('Any')],
            ['key' => 'alliance', 'description' => __('Alliance')],
            ['key' => 'horde', 'description' => __('Horde')],
        ],
    ];
}
?>
@include('common.general.inline', ['path' => 'common/maps/map', 'options' => array_merge([
    'embed' => $embed,
    'edit' => $edit,
    'sandbox' => $sandboxMode,
    'defaultEnemyVisualType' => $enemyVisualType,
    'defaultUnkilledEnemyOpacity' => $unkilledEnemyOpacity,
    'defaultUnkilledImportantEnemyOpacity' => $unkilledImportantEnemyOpacity,
    'defaultEnemyAggressivenessBorder' => $defaultEnemyAggressivenessBorder,
    'noUI' => $noUI,
    'gestureHandling' => $gestureHandling,
    'zoomToContents' => $zoomToContents,
    'hiddenMapObjectGroups' => $hiddenMapObjectGroups,
    'defaultZoom' => $defaultZoom,
    'showAttribution' => $showAttribution,
    // @TODO Temp fix
    'npcsMinHealth' => $mapContext['npcsMinHealth'],
    'npcsMaxHealth' => $mapContext['npcsMaxHealth'],
    'dungeonroute' => $dungeonroute ?? null,
    'levelMin' => config('keystoneguru.levels.min'),
    'levelMax' => config('keystoneguru.levels.max'),
], $adminOptions)])

@section('scripts')
    {{-- Make sure we don't override the scripts of the page this thing is included in --}}
    @parent

    @include('common.handlebars.groupsetup')

    @include('common.general.statemanager', [
        // Required by echo to join the correct channels
        'appType' => env('APP_TYPE'),
        'echo' => $echo,
        'paidTiers' => Auth::check() ? $user->getPaidTiers() : collect(),
        'userData' => $user,
        'mapContext' => $mapContext,
    ])
    <script>
        var dungeonMap;

        $(function () {
            let code = _inlineManager.getInlineCode('common/maps/map');

            // Expose the dungeon map in a global variable
            dungeonMap = code.getDungeonMap();
        });
    </script>

    @if($dungeon->isSiegeOfBoralus())
        <script id="map_faction_display_controls_template" type="text/x-handlebars-template">
        <div id="map_faction_display_controls" class="leaflet-draw-section">
            <div class="leaflet-draw-toolbar leaflet-bar leaflet-draw-toolbar-top">
            <?php
            $i = 0;
            foreach(\App\Models\Faction::where('name', '<>', 'Unspecified')->get() as $faction) {
            ?>
            <a class="map_faction_display_control map_controls_custom" href="#"
               data-faction="{{ strtolower($faction->name) }}"
                       title="{{ $faction->name }}">
                        <i class="{{ $i === 0 ? 'fas' : 'far' }} fa-circle radiobutton"
                           style="width: 15px"></i>
                        <img src="{{ $faction->iconfile->icon_url }}" class="select_icon faction_icon"
                             data-toggle="tooltip" title="{{ $faction->name }}"/>
                </a>
                <?php
            $i++;
            } ?>
            </div>
            <ul class="leaflet-draw-actions"></ul>
        </div>

        </script>
    @endif
@endsection

@if(!$noUI)
    @include('common.maps.controls.header', [
        'title' => isset($dungeonroute) ? $dungeonroute->title : $dungeon->name,
        'echo' => $echo,
        'dungeonroute' => $dungeonroute,
    ])


    @if($edit)
        @include('common.maps.controls.draw', [
            'isAdmin' => $isAdmin,
            'floors' => $dungeon->floors,
            'selectedFloorId' => $floorId,
        ])
    @else
        @include('common.maps.controls.view', [
            'isAdmin' => $isAdmin,
            'floors' => $dungeon->floors,
            'selectedFloorId' => $floorId,
            'dungeonroute' => $dungeonroute,
        ])
    @endif

    @if(!$isAdmin)
        @include('common.maps.controls.pulls', [
            'edit' => $edit,
            'dungeonroute' => $dungeonroute,
        ])
    @endif

    @include('common.maps.controls.enemyinfo')
@endif


<div id="map" class="virtual-tour-element {{$mapClasses}}" data-position="auto">

</div>

@if(!$noUI)

    @if(!$adFree && $showAds)
        @if($isMobile)
            @include('common.thirdparty.adunit', ['id' => 'map_footer', 'type' => 'footer'])
        @else
            <footer class="fixed-bottom">
                <div class="container p-0" style="width: 728px">
                    @include('common.thirdparty.adunit', ['id' => 'map_footer', 'type' => 'footer', 'class' => 'map_ad_background', 'map' => true])
                </div>
            </footer>
        @endif
    @endif



    @isset($dungeonroute)
        @component('common.general.modal', ['id' => 'userreport_dungeonroute_modal'])
            @include('common.modal.userreport.dungeonroute', ['dungeonroute' => $dungeonroute])
        @endcomponent

        @component('common.general.modal', ['id' => 'userreport_enemy_modal'])
            @include('common.modal.userreport.enemy')
        @endcomponent

        @component('common.general.modal', ['id' => 'share_modal'])
            @include('common.modal.share', ['show' => $show['share'], 'dungeonroute' => $dungeonroute])
        @endcomponent
    @endisset





    @component('common.general.modal', ['id' => 'route_settings_modal', 'size' => 'xl'])
        <?php $hasRouteSettings = isset($dungeonroute) && !$dungeonroute->isSandbox() && $edit; ?>
        <ul class="nav nav-tabs" role="tablist">
            @if( $hasRouteSettings )
                <li class="nav-item">
                    <a class="nav-link active" id="edit_route_tab" data-toggle="tab" href="#edit" role="tab"
                       aria-controls="edit_route" aria-selected="true">
                        {{ __('Route') }}
                    </a>
                </li>
            @endisset
            <li class="nav-item">
                <a class="nav-link {{ $hasRouteSettings ? '' : 'active' }}"
                   id="edit_route_map_settings_tab" data-toggle="tab" href="#map-settings" role="tab"
                   aria-controls="edit_route_map_settings" aria-selected="false">
                    {{ __('Map settings') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="edit_route_pull_settings_tab" data-toggle="tab" href="#pull-settings" role="tab"
                   aria-controls="edit_route_pull_settings" aria-selected="false">
                    {{ __('Pull settings') }}
                </a>
            </li>
        </ul>

        <div class="tab-content">
            @if($hasRouteSettings)
                <div id="edit" class="tab-pane fade show active mt-3" role="tabpanel" aria-labelledby="edit_route_tab">
                    @include('common.forms.createroute', ['dungeonroute' => $dungeonroute])
                </div>
            @endisset
            <div id="map-settings" class="tab-pane fade {{ $hasRouteSettings ? '' : 'show active' }} mt-3"
                 role="tabpanel" aria-labelledby="edit_route_map_settings_tab">
                @include('common.forms.mapsettings', ['dungeonroute' => $dungeonroute, 'edit' => $edit])
            </div>
            <div id="pull-settings" class="tab-pane fade mt-3" role="tabpanel"
                 aria-labelledby="edit_route_pull_settings_tab">
                @include('common.forms.pullsettings', ['dungeonroute' => $dungeonroute, 'edit' => $edit])
            </div>
        </div>

    @endcomponent
@endif