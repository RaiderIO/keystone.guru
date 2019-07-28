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
$routePublicKey = isset($dungeonroute) ? $dungeonroute->public_key : '';
// Set the key to 'try' if try mode is enabled
$tryMode = isset($tryMode) && $tryMode ? true : false;
// Set the enemy forces of the current route. May not be set if just editing the route from admin
$routeEnemyForces = isset($dungeonroute) ? $dungeonroute->enemy_forces : 0;
// For Siege of Boralus
$routeFaction = isset($dungeonroute) ? strtolower($dungeonroute->faction->name) : 'any';
$routeBeguilingPreset = isset($dungeonroute) ? $dungeonroute->beguiling_preset : 1;
// Grab teeming from the route, if it's not set, grab it from a variable, or just be false. Admin teeming is always true.
$teeming = isset($dungeonroute) ? $dungeonroute->teeming : ((isset($teeming) && $teeming) || $isAdmin) ? true : false;
$enemyVisualType = isset($enemyVisualType) ? $enemyVisualType : 'npc_class';

// Easy switch
$isProduction = config('app.env') === 'production';
// Show ads or not
$showAds = isset($showAds) ? $showAds : true;
// If we should show ads, are logged in, user has paid for no ads, or we're not in production..
if (($showAds && Auth::check() && $user->hasPaidTier('ad-free')) || !$isProduction) {
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
// Construct the data of the beguiling NPCs
$maxBeguilingPresets = DB::table('enemies')->selectRaw('MAX(`beguiling_preset`) as max')->where('floor_id', $floorId)->get()->first()->max;
$beguilingPresets = [];
for ($i = 1; $i <= $maxBeguilingPresets; $i++) {
    $beguilingPresets[] = ['index' => $i, 'description' => sprintf(__('Preset %s'), $i)];
}

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
        'teeming' => [
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
@include('common.general.inline', ['path' => 'common/maps/map', 'options' => array_merge([
    'username' => Auth::check() ? $user->name : '',
    // Only activate Echo when we are a member of the team in which this route is a member of
    'echo' => !isset($dungeonroute) || $dungeonroute->team === null ? false : $dungeonroute->team->isUserMember($user),
    'floorId' => $floorId,
    'edit' => $edit,
    'try' => $tryMode,
    'dungeonroute' => [
        'publicKey' => $routePublicKey,
        'faction' => $routeFaction,
        'beguilingPreset' => $routeBeguilingPreset
    ],
    'beguilingPresets' => $beguilingPresets,
    'defaultEnemyVisualType' => $enemyVisualType,
    'teeming' => $teeming,
    'noUI' => $noUI,
    'hiddenMapObjectGroups' => $hiddenMapObjectGroups,
    'defaultZoom' => $defaultZoom,
    'showAttribution' => $showAttribution,
    'npcsMinHealth' => $dungeon->getNpcsMinHealth(),
    'npcsMaxHealth' => $dungeon->getNpcsMaxHealth()
], $adminOptions)])

@section('scripts')
    {{-- Make sure we don't override the scripts of the page this thing is included in --}}
    @parent

    @include('common.general.statemanager')
    <script>
        // Data of the dungeon(s) we're selecting in the map
        var dungeonData = {!! $dungeon !!};
        var dungeonRouteEnemyForces = {{ $routeEnemyForces }};
        var isMapAdmin = {{ $isAdmin ? 'true' : 'false' }};
        var factionsData = {!! \App\Models\Faction::where('name', '<>', 'Unspecified')->with('iconfile')->get() !!};
        var classColors = {!! \App\Models\CharacterClass::all()->pluck('color') !!};

        var dungeonMap;

        $(function () {
            let code = _inlineManager.getInlineCode('common/maps/map');

            // Must be done here, otherwise it's too soon. I don't really know why either, but otherwise the draw controls
            // get fucked up
            code.initDungeonMap();

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

    <script id="map_path_edit_popup_template" type="text/x-handlebars-template">
        <div id="map_path_edit_popup_inner" class="popupCustom">
            <div class="form-group">
                {!! Form::label('map_path_edit_popup_color_@{{id}}', __('Color')) !!}
                {!! Form::color('map_path_edit_popup_color_@{{id}}', null, ['class' => 'form-control']) !!}

                @php($classes = \App\Models\CharacterClass::all())
                @php($half = ($classes->count() / 2))
                @for($i = 0; $i < $classes->count(); $i++)
                    @php($class = $classes->get($i))
                    @if($i % $half === 0)
                        <div class="row no-gutters pt-1">
                            @endif
                            <div class="col map_polyline_edit_popup_class_color border-dark"
                                 data-color="{{ $class->color }}"
                                 style="background-color: {{ $class->color }};">
                            </div>
                            @if($i % $half === $half - 1)
                        </div>
                    @endif
                @endfor
            </div>
            {!! Form::button(__('Submit'), ['id' => 'map_path_edit_popup_submit_@{{id}}', 'class' => 'btn btn-info']) !!}
        </div>
    </script>

    <script id="map_brushline_edit_popup_template" type="text/x-handlebars-template">
        <div id="map_brushline_edit_popup_inner" class="popupCustom">
            <div class="form-group">
                {!! Form::label('map_brushline_edit_popup_color_@{{id}}', __('Color')) !!}
                {!! Form::color('map_brushline_edit_popup_color_@{{id}}', null, ['class' => 'form-control']) !!}

                @php($classes = \App\Models\CharacterClass::all())
                @php($half = ($classes->count() / 2))
                @for($i = 0; $i < $classes->count(); $i++)
                    @php($class = $classes->get($i))
                    @if($i % $half === 0)
                        <div class="row no-gutters pt-1">
                            @endif
                            <div class="col map_polyline_edit_popup_class_color border-dark"
                                 data-color="{{ $class->color }}"
                                 style="background-color: {{ $class->color }};">
                            </div>
                            @if($i % $half === $half - 1)
                        </div>
                    @endif
                @endfor
            </div>
            <div class="form-group">
                {!! Form::label('map_brushline_edit_popup_weight_@{{id}}', __('Weight')) !!}
                {!! Form::select('map_brushline_edit_popup_weight_@{{id}}', [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6], 3,
                ['id' => 'map_brushline_edit_popup_weight_@{{id}}', 'class' => 'form-control selectpicker']) !!}
            </div>
            {!! Form::button(__('Submit'), ['id' => 'map_brushline_edit_popup_submit_@{{id}}', 'class' => 'btn btn-info']) !!}
        </div>
    </script>

    <script id="map_map_comment_edit_popup_template" type="text/x-handlebars-template">
        <div id="map_map_comment_edit_popup_inner" class="popupCustom">
            <div class="form-group">
                {!! Form::label('map_map_comment_edit_popup_comment_@{{id}}', __('Comment')) !!}
                {!! Form::textarea('map_map_comment_edit_popup_comment_@{{id}}', null, ['class' => 'form-control', 'cols' => '50', 'rows' => '5']) !!}
            </div>
            <div class="form-group">
                @if($isAdmin)
                    {!! Form::hidden('map_map_comment_edit_popup_always_visible_@{{id}}', 1, []) !!}
                @endif
            </div>
            {!! Form::button(__('Submit'), ['id' => 'map_map_comment_edit_popup_submit_@{{id}}', 'class' => 'btn btn-info']) !!}
        </div>
    </script>

    @if(!$isAdmin)
        <script id="enemy_edit_popup_template" type="text/x-handlebars-template">
            <div id="enemy_edit_popup_inner" class="popupCustom">
                @php($raidMarkers = \App\Models\RaidMarker::all())
                @for($i = 0; $i < $raidMarkers->count(); $i++)
                    @php($raidMarker = $raidMarkers->get($i))
                    @if($i % 4 === 0)
                        <div class="row no-gutters">
                            @endif
                            <div class="enemy_raid_marker_icon enemy_raid_marker_icon_{{ $raidMarker->name }}"
                                 data-name="{{ $raidMarker->name }}">
                            </div>
                            @if($i % 4 === 3)
                        </div>
                    @endif
                @endfor
                <div id="enemy_raid_marker_clear_@{{id}}" class="btn btn-warning col-12 mt-2"><i
                            class="fa fa-times"></i> {{ __('Clear marker') }}</div>
            </div>
        </script>
    @endif
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

            <!-- Enemy forces controls are injected here through enemyforces.js -->
            <div class="row m-auto text-center">
                <div id="edit_route_enemy_forces_container" class="col">

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