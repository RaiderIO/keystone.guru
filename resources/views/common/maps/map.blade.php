<?php
/** @var $npcs \Illuminate\Support\Collection */
/** @var \App\User $user */
$user = Auth::user();
$isAdmin = isset($admin) && $admin;
/** @var App\Models\Dungeon $dungeon */
/** @var App\Models\DungeonRoute $dungeonroute */
// Enabled by default if it's not set, but may be explicitly disabled
// Do not show if it does not make sense (only one floor)
$edit = isset($edit) && $edit ? true : false;
$routeTeam = isset($dungeonroute) ? $dungeonroute->team_id : -1;
$routePublicKey = isset($dungeonroute) ? $dungeonroute->public_key : 'admin';
$routeSeasonalIndex = isset($dungeonroute) ? $dungeonroute->seasonal_index : 0;
$routeKillZones = isset($dungeonroute) ? \App\Models\KillZone::where('dungeon_route_id', $dungeonroute->id)->orderBy('index')->get() : new \Illuminate\Database\Eloquent\Collection();
// Set the key to 'try' if try mode is enabled
$tryMode = isset($tryMode) && $tryMode ? true : false;
// Set the enemy forces of the current route. May not be set if just editing the route from admin
$routeEnemyForces = isset($dungeonroute) ? $dungeonroute->getEnemyForces() : 0;
// For Siege of Boralus
$routeFaction = isset($dungeonroute) ? strtolower($dungeonroute->faction->name) : 'any';
// Grab teeming from the route, if it's not set, grab it from a variable, or just be false. Admin teeming is always true.
$teeming = (isset($dungeonroute) ? $dungeonroute->teeming : ((isset($teeming) && $teeming) || $isAdmin)) ? true : false;
$pullGradient = (isset($dungeonroute) ? $dungeonroute->pull_gradient : '');
$pullGradientApplyAlways = (isset($dungeonroute) ? $dungeonroute->pull_gradient_apply_always : false);
$enemyVisualType = isset($_COOKIE['enemy_display_type']) ? $_COOKIE['enemy_display_type'] : 'npc_class';

// Easy switch
$isProduction = config('app.env') === 'production';
// Show ads or not
$showAds = isset($showAds) ? $showAds : true;
// If we should show ads, are logged in, user has paid for no ads, or we're not in production..
if (($showAds && Auth::check() && $user->hasPaidTier(\App\Models\PaidTier::AD_FREE)) || !$isProduction) {
    $showAds = false;
}
// No UI on the map
$noUI = isset($noUI) && $noUI ? true : false;
// Default zoom for the map
$defaultZoom = isset($defaultZoom) ? $defaultZoom : 2;
// By default hidden elements
$hiddenMapObjectGroups = isset($hiddenMapObjectGroups) ? $hiddenMapObjectGroups : [];
// Floor id to display (bit ugly with JS, but it works)
$floorId = isset($floorId) ? $floorId : $dungeon->floors->first()->id;
// Show the attribution
$showAttribution = isset($showAttribution) && !$showAttribution ? false : true;

// Additional options to pass to the map when we're in an admin environment
$adminOptions = [];
if ($isAdmin) {
    // Build options for displayed NPCs
    $npcOptions = [];
    foreach ($npcs as $npc) {
        $npcOptions[] = ['id' => $npc->id, 'name' => $npc->name, 'dungeon_id' => $npc->dungeon_id];
    }

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
        // Display options for changing the NPC of an enemy
        'npcs' => $npcOptions
    ];
}
?>
@php($dependencies = $edit && !$tryMode && !$isAdmin ? ['dungeonroute/edit'] : null)
@include('common.general.inline', ['path' => 'common/maps/map', 'options' => array_merge([
    'username' => Auth::check() ? $user->name : '',
    // Only activate Echo when we are a member of the team in which this route is a member of
    'echo' => !isset($dungeonroute) || $dungeonroute->team === null ? false : $dungeonroute->team->isUserMember($user),
    // Required by echo to join the correct channels
    'appType' => env('APP_TYPE'),
    'floorId' => $floorId,
    'edit' => $edit,
    'try' => $tryMode,
    'defaultEnemyVisualType' => $enemyVisualType,
    'noUI' => $noUI,
    'hiddenMapObjectGroups' => $hiddenMapObjectGroups,
    'defaultZoom' => $defaultZoom,
    'showAttribution' => $showAttribution,
    'npcsMinHealth' => $dungeon->getNpcsMinHealth(),
    'npcsMaxHealth' => $dungeon->getNpcsMaxHealth(),
    'dependencies' => $dependencies
], $adminOptions)])

@section('scripts')
    {{-- Make sure we don't override the scripts of the page this thing is included in --}}
    @parent

    @include('common.general.statemanager', array_merge([
        'mapIconTypes' => \App\Models\MapIconType::all(),
        'classColors' => \App\Models\CharacterClass::all()->pluck('color'),
        'raidMarkers' => \App\Models\RaidMarker::all(),
        'factions' => \App\Models\Faction::where('name', '<>', 'Unspecified')->with('iconfile')->get(),
        'killZones' => $routeKillZones,
        'dungeonData' => $dungeon,
        'paidTiers' => Auth::check() ? $user->getPaidTiers() : collect(),
        'dungeonroute' => [
            'publicKey' => $routePublicKey,
            'faction' => $routeFaction,
            'enemyForces' => $routeEnemyForces,
            'seasonalIndex' => $routeSeasonalIndex,
            'teeming' => $teeming,
            'teamId' => $routeTeam,
            'pullGradient' => $pullGradient,
            'pullGradientApplyAlways' => $pullGradientApplyAlways
        ]
    ], (new \App\Service\DungeonRoute\EnemiesListService())->listEnemies($dungeon->id, $isAdmin, $routePublicKey === 'admin' ? null : $routePublicKey)))
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

<div id="map" class="virtual-tour-element"
     data-position="auto">

</div>

<header class="fixed-top route_echo_status">
    <!-- Draw actions are injected here through echocontrols.js -->
    <div id="route_echo_container" class="container">
    </div>
</header>
@if($edit)
    <footer class="fixed-bottom route_manipulation_tools">
        <div class="container">
            <!-- Draw actions are injected here through enemyforces.js -->
            <div class="row m-auto text-center">
                <div id="edit_route_draw_actions_container" class="col">

                </div>
            </div>

            <!-- Draw controls are injected here through drawcontrols.js -->
            <div id="edit_route_draw_container" class="row">

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