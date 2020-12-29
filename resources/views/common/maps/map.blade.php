<?php
/** @var \App\User $user */
/** @var \App\Logic\MapContext\MapContext $mapContext */
$user = Auth::user();
$isAdmin = isset($admin) && $admin;
/** @var App\Models\Dungeon $dungeon */
/** @var App\Models\DungeonRoute $dungeonroute */
$embed = isset($embed) && $embed;
$edit = isset($edit) && $edit;

$mapClasses = isset($mapClasses) ? $mapClasses : '';

// Set the key to 'sandbox' if sandbox mode is enabled
$sandboxMode = isset($sandboxMode) && $sandboxMode;
$enemyVisualType = (isset($enemyVisualType) ? $enemyVisualType : isset($_COOKIE['enemy_display_type'])) ? $_COOKIE['enemy_display_type'] : 'enemy_portrait';
// Allow echo to be overridden
$echo = isset($echo) ? $echo : Auth::check() && !$sandboxMode;

// Easy switch
$isProduction = config('app.env') === 'production';
// Show ads or not
$showAds = isset($showAds) ? $showAds : true;
// If we should show ads, are logged in, user has paid for no ads, or we're not in production..
if (($showAds && Auth::check() && $user->hasPaidTier(\App\Models\PaidTier::AD_FREE)) || !$isProduction) {
    $showAds = false;
}
// No UI on the map
$noUI = isset($noUI) && $noUI;
// Default zoom for the map
$defaultZoom = isset($defaultZoom) ? $defaultZoom : 2;
// By default hidden elements
$hiddenMapObjectGroups = isset($hiddenMapObjectGroups) ? $hiddenMapObjectGroups : [];
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
    'noUI' => $noUI,
    'hiddenMapObjectGroups' => $hiddenMapObjectGroups,
    'defaultZoom' => $defaultZoom,
    'showAttribution' => $showAttribution,
    // @TODO Temp fix
    'npcsMinHealth' => $mapContext['npcsMinHealth'],
    'npcsMaxHealth' => $mapContext['npcsMaxHealth'],
], $adminOptions)])

@section('scripts')
    {{-- Make sure we don't override the scripts of the page this thing is included in --}}
    @parent

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

    <script id="map_faction_display_controls_template" type="text/x-handlebars-template">
        <div id="map_faction_display_controls" class="leaflet-draw-section">
            <div class="leaflet-draw-toolbar leaflet-bar leaflet-draw-toolbar-top">
                @php($i = 0)
        @foreach(\App\Models\Faction::where('name', '<>', 'Unspecified')->get() as $faction)
            <a class="map_faction_display_control map_controls_custom" href="#"
               data-faction="{{ strtolower($faction->name) }}"
                       title="{{ $faction->name }}">
                        <i class="{{ $i === 0 ? 'fas' : 'far' }} fa-circle radiobutton"
                           style="width: 15px"></i>
                        <img src="{{ $faction->iconfile->icon_url }}" class="select_icon faction_icon"
                             data-toggle="tooltip" title="{{ $faction->name }}"/>
                        @php($i++)
            </a>
@endforeach
        </div>
        <ul class="leaflet-draw-actions"></ul>
    </div>







    </script>
@endsection

<div id="map" class="virtual-tour-element {{$mapClasses}}" data-position="auto">

</div>
@if(!$edit)
    <header class="fixed-top route_echo_top_header">
        <div class="container">
            <!-- Echo controls injected here through echocontrols.js -->
            <span id="route_echo_container" class="text-center">

        </span>
        </div>
    </header>
@endif

@component('common.general.modal', ['id' => 'userreport_dungeonroute_modal'])
    @include('common.userreport.dungeonroute')
@endcomponent

@component('common.general.modal', ['id' => 'userreport_enemy_modal'])
    @include('common.userreport.enemy')
@endcomponent

@if($edit && !$noUI)
    <footer class="fixed-bottom route_manipulation_tools">
        <div class="container">
            <!-- Draw actions are injected here through drawcontrols.js -->
            <div class="row m-auto text-center">
                <div id="edit_route_draw_actions_container" class="col">

                </div>
            </div>

            <div class="row">
                <div class="col">
                    <!-- Draw controls are injected here through drawcontrols.js -->
                    <div id="edit_route_draw_container" class="row">


                    </div>
                </div>
                <div class="col route_echo mt-2 mb-2">
                    <!-- Echo controls injected here through echocontrols.js -->
                    <span id="route_echo_container" class="text-center">

                    </span>
                </div>
            </div>
        </div>
    </footer>
@endif

@if($showAds)
    @php($isMobile = (new \Jenssegers\Agent\Agent())->isMobile())
    @if($isMobile)
        <div id="map_ad_horizontal">
            @include('common.thirdparty.adunit', ['type' => 'mapsmall_horizontal'])
        </div>
    @else
        <div id="map_ad_vertical">
            @include('common.thirdparty.adunit', ['type' => 'mapsmall'])
        </div>
    @endif
@endif