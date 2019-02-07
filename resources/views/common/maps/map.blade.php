<?php
$isAdmin = isset($admin) && $admin;
/** @var App\Models\Dungeon $dungeon */
/** @var App\Models\DungeonRoute $dungeonroute */
// Enabled by default if it's not set, but may be explicitly disabled
// Do not show if it does not make sense (only one floor)
$edit = isset($edit) && $edit ? 'true' : 'false';
$routePublicKey = isset($dungeonroute) ? $dungeonroute->public_key : '';
$routeEnemyForces = isset($dungeonroute) ? $dungeonroute->enemy_forces : 0;
// For Siege of Boralus
$routeFaction = isset($dungeonroute) ? strtolower($dungeonroute->faction->name) : 'any';
// Grab teeming from the route, if it's not set, grab it from a variable, or just be false. Admin teeming is always true.
$teeming = isset($dungeonroute) ? $dungeonroute->teeming : ((isset($teeming) && $teeming) || $isAdmin) ? 'true' : 'false';
$enemyVisualType = isset($enemyVisualType) ? $enemyVisualType : 'aggressiveness';
// Show ads or not
$showAds = isset($showAds) ? $showAds : true;
// Hide ads if this page shows them, but the user has ad-free tier
if ($showAds && Auth::check() && Auth::user()->hasPaidTier('ad-free')) {
    $showAds = false;
}
// No UI on the map
$noUI = isset($noUI) && $noUI ? 'true' : 'false';
// Default zoom for the map
$defaultZoom = isset($defaultZoom) ? $defaultZoom : 2;
// By default hidden elements
$hiddenMapObjectGroups = isset($hiddenMapObjectGroups) ? $hiddenMapObjectGroups : [];
// Floor id to display (bit ugly with JS, but it works)
$floorId = isset($floorId) ? $floorId : '_dungeonData.floors[0].id';
// Show the attribution
$showAttribution = isset($showAttribution) && !$showAttribution ? 'false' : 'true';

$introTexts = [
    __('Welcome to Keystone.guru! To begin, this is the sidebar. Here you can adjust options for your route or view information about it.'),
    __('You can use this button to hide or show the sidebar.'),
    __('This label indicates the current progress with enemy forces. Use \'killzones\' to mark an enemy as killed and see this label updated (more on this in a bit!).'),

    __('These are your route manipulation tools.'),
    __('You can draw routes with this tool. Click it, then draw a route (a line) from A to B, with as many points are you like. Once finished, you can click
    the line on the map to change its color. You can add as many routes as you want, use the colors to your advantage. Color the line yellow for Rogue Shrouding,
    or purple for a Warlock Gateway, for example.'),
    __('This is a \'killzone\'. You use these zones to indicate what enemies you are killing, and most importantly, where. Place a zone on the map and click it again.
    You can then select any enemy on the map that has not already \'been killed\' by another kill zone. When you select a pack, you automatically select all enemies in the pack.
    Once you have selected enemies your enemy forces (top right) will update to reflect your new enemy forces counter.'),
    __('Use this control to place comments on the map, for example to indicate you\'re skipping a patrol or to indicate details and background info in your route.'),
    __('Use this control to free draw lines on your route.'),

    __('This is the edit button. You can use it to adjust your created routes, move your killzones or comments.'),
    __('This is the delete button. Click it once, then select the controls you wish to delete. Deleting happens in a preview mode, you have to confirm your delete in a label
    that pops up once you press the button. You can then confirm or cancel your staged changes. If you confirm the deletion, there is no turning back!'),

    __('The color and weight selection affect newly placed free drawn lines and routes. Killzones get the selected color by default.'),

    __('Here you can select different visualization options.'),
    __('You can chose from multiple different visualizations to help you quickly find the information you need.'),

    __('If your dungeon has multiple floors, this is where you can change floors. You can also click the doors on the map to go to the next floor.'),

    __('You can use these controls to zoom the map in or out. You can also use the mouse scrollwheel if you\'re on a computer.'),
    __('These are your visibility toggles. You can hide enemies, enemy patrols, enemy packs, your own routes, your own killzones, all map comments, start markers and floor switch markers.')
];
?>

@section('scripts')
    {{-- Make sure we don't override the scripts of the page this thing is included in --}}
    @parent

    <script>
        // Data of the dungeon(s) we're selecting in the map
        var _dungeonData = {!! $dungeon !!};
        var dungeonRouteEnemyForces = {{ $routeEnemyForces }};

        var dungeonMap;

        // Options for the dungeonmap object
        var options = {
            floorId: {{ $floorId }},
            edit: {{ $edit }},
            dungeonroute: {
                publicKey: '{{ $routePublicKey }}',
                faction: '{{ $routeFaction }}'
            },
            defaultEnemyVisualType: '{{ $enemyVisualType }}',
            teeming: {{ $teeming }},
            noUI: {{ $noUI }},
            hiddenMapObjectGroups: {!!  json_encode($hiddenMapObjectGroups) !!},
            defaultZoom: {{ $defaultZoom }},
            showAttribution: {{ $showAttribution }}
        };

        $(function () {

            @if($isAdmin)
                dungeonMap = new AdminDungeonMap('map', _dungeonData, options);
            @else
                dungeonMap = new DungeonMap('map', _dungeonData, options);
            @endif

            // Support not having a sidebar (preview map)
            if (typeof (_switchDungeonFloorSelect) !== 'undefined') {
                $(_switchDungeonFloorSelect).change(function () {
                    // Pass the new floor ID to the map
                    dungeonMap.currentFloorId = $(_switchDungeonFloorSelect).val();
                    dungeonMap.refreshLeafletMap();
                });
            }

            $('#start_virtual_tour').bind('click', function () {
                introjs().start();
            });

            // Bind leaflet virtual tour classes
            var selectors = [
                ['#sidebar', 'right'],
                ['#sidebarToggle', 'right'],
                ['.enemy_forces_container', 'right'],

                ['.route_manipulation_tools', 'right'],
                ['.leaflet-draw-draw-route', 'right'],
                ['.leaflet-draw-draw-killzone', 'right'],
                ['.leaflet-draw-draw-mapcomment', 'right'],
                ['.leaflet-draw-draw-brushline', 'right'],

                ['.leaflet-draw-edit-edit', 'right'],
                ['.leaflet-draw-edit-remove', 'right'],

                ['#edit_route_freedraw_options_container', 'right'],

                ['.visibility_tools', 'right'],
                ['#map_enemy_visuals', 'right'],
                ['.floor_selection', 'right'],

                ['.leaflet-control-zoom', 'left'],
                ['#map_controls .leaflet-draw-toolbar', 'left'],
            ];
            var texts = {!! json_encode($introTexts) !!};

            dungeonMap.register('map:refresh', null, function () {
                // Upon map refresh, re-init the tutorial selectors
                for (var i = 0; i < selectors.length; i++) {
                    var $selector = $(selectors[i][0]);
                    $selector.attr('data-intro', texts[i]);
                    $selector.attr('data-position', selectors[i][1]);
                    $selector.attr('data-step', i + 1);
                }

                // If the map is opened on mobile hide the sidebar
                if (isMobile()) {
                    var fn = function () {
                        if (typeof _hideSidebar === 'function') {
                            // @TODO This introduces a dependency on sidebar, but sidebar loads before dungeonMap is instantiated
                            _hideSidebar();
                        }
                    };
                    dungeonMap.leafletMap.off('move', fn);
                    dungeonMap.leafletMap.on('move', fn);
                }
            });

            // Refresh the map; draw the layers on it
            dungeonMap.refreshLeafletMap();
        });
    </script>

    <script id="map_enemy_forces_template" type="text/x-handlebars-template">
        <div id="map_enemy_forces" class="font-weight-bold" data-toggle="tooltip">
            <div class="row">
                <div class="col">
                    <span id="map_enemy_forces_numbers">
                        <i id="map_enemy_forces_success" class="fas fa-check-circle" style="display: none;"></i>
                        <i id="map_enemy_forces_warning" class="fas fa-exclamation-triangle" style="display: none;"></i>
                        <span id="map_enemy_forces_count">0</span>/@{{ enemy_forces_total }}
                        (<span id="map_enemy_forces_percent">0</span>%)
                    </span>
                </div>
            </div>
        </div>
    </script>

    <script id="map_enemy_visuals_template" type="text/x-handlebars-template">
        <div id="map_enemy_visuals" class="leaflet-draw-section">
            <div class="form-group">
                <?php
                $visuals = [];
                $visuals['aggressiveness'] = __('Aggressiveness');
                $visuals['enemy_forces'] = __('Enemy forces');
                ?>
                {!! Form::select('map_enemy_visuals_dropdown', $visuals, 0, ['id' => 'map_enemy_visuals_dropdown', 'class' => 'form-control selectpicker']) !!}
            </div>
            @if($isAdmin)
                <div class="form-group">
                    <div class="font-weight-bold">
                        {{ __('MDT enemy mapping') }}:
                    </div>
                    {!! Form::checkbox('map_enemy_visuals_map_mdt_clones_to_enemies', 1, false,
                        ['id' => 'map_enemy_visuals_map_mdt_clones_to_enemies', 'class' => 'form-control left_checkbox']) !!}
                </div>
            @endif
        </div>
    </script>

    <script id="map_controls_template" type="text/x-handlebars-template">
        <div id="map_controls" class="leaflet-draw-section">
            <div class="leaflet-draw-toolbar leaflet-bar leaflet-draw-toolbar-top">
                @{{#mapobjectgroups}}
                <a id='map_controls_hide_@{{name}}' class="map_controls_custom" href="#" title="@{{title}}">
                    <i id='map_controls_hide_@{{name}}_checkbox' class="fas fa-check-square" style="width: 15px"></i>
                    <i class="fas @{{fa_class}}" style="width: 15px"></i>
                    <span class="sr-only">@{{title}}</span>
                </a>
                @{{/mapobjectgroups}}
            </div>
            <ul class="leaflet-draw-actions"></ul>
        </div>
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

    <script id="map_enemy_tooltip_template" type="text/x-handlebars-template">
        <div class="map_enemy_tooltip leaflet-draw-section">
            <div class="row">
                <div class="col-5 no-gutters">{{ __('Name') }} </div>
                <div class="col-7 no-gutters">@{{ npc_name }}</div>
            </div>
            <div class="row">
                <div class="col-5 no-gutters">{{ __('Enemy forces') }} </div>
                <div class="col-7 no-gutters">@{{{ enemy_forces }}}</div>
            </div>
            <div class="row">
                <div class="col-5 no-gutters">{{ __('Base health') }} </div>
                <div class="col-7 no-gutters">@{{ base_health }}</div>
            </div>
            <div class="row">
                <div class="col-5 no-gutters">{{ __('Teeming') }} </div>
                <div class="col-7 no-gutters">@{{ teeming }}
                </div>
            </div>
            @auth
                @if(Auth::user()->hasRole('admin'))
                    <div class="row">
                        <div class="col-12 font-weight-bold">
                            {{ __('Admin only') }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-5 no-gutters">{{ __('ID') }} </div>
                        <div class="col-7 no-gutters">@{{ id }}</div>
                    </div>
                    <div class="row">
                        <div class="col-5 no-gutters">{{ __('Faction') }} </div>
                        <div class="col-7 no-gutters">@{{ faction }}</div>
                    </div>
                    <div class="row">
                        <div class="col-5 no-gutters">{{ __('NPC_ID') }} </div>
                        <div class="col-7 no-gutters">@{{ npc_id }} (@{{ npc_id_type }})</div>
                    </div>
                    <div class="row">
                        <div class="col-5 no-gutters">{{ __('Pack') }} </div>
                        <div class="col-7 no-gutters">@{{ attached_to_pack }}</div>
                    </div>
                    <div class="row">
                        <div class="col-5 no-gutters">{{ __('MDT') }} </div>
                        <div class="col-7 no-gutters">@{{ is_mdt }}</div>
                    </div>
                    <div class="row">
                        <div class="col-5 no-gutters">{{ __('MDT_ID') }} </div>
                        <div class="col-7 no-gutters">@{{ mdt_id }}</div>
                    </div>
                    <div class="row">
                        <div class="col-5 no-gutters">{{ __('ENEMY_ID') }} </div>
                        <div class="col-7 no-gutters">@{{ enemy_id }}</div>
                    </div>
                    <div class="row">
                        <div class="col-5 no-gutters">{{ __('Visual') }} </div>
                        <div class="col-7 no-gutters">@{{ visual }}</div>
                    </div>
                @endif
            @endauth
        </div>
    </script>

    <script id="map_route_edit_popup_template" type="text/x-handlebars-template">
        <div id="map_route_edit_popup_inner" class="popupCustom">
            <div class="form-group">
                {!! Form::label('map_route_edit_popup_color_@{{id}}', __('Color')) !!}
                {!! Form::color('map_route_edit_popup_color_@{{id}}', null, ['class' => 'form-control']) !!}

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
            {!! Form::button(__('Submit'), ['id' => 'map_route_edit_popup_submit_@{{id}}', 'class' => 'btn btn-info']) !!}
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



    <script id="map_killzone_edit_popup_template" type="text/x-handlebars-template">
        <div id="map_killzone_edit_popup_inner" class="popupCustom">
            <div class="form-group">
                {!! Form::label('map_killzone_edit_popup_color_@{{id}}', __('Color')) !!}
                {!! Form::color('map_killzone_edit_popup_color_@{{id}}', null, ['class' => 'form-control']) !!}
            </div>
            {!! Form::button(__('Submit'), ['id' => 'map_killzone_edit_popup_submit_@{{id}}', 'class' => 'btn btn-info']) !!}
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

    <script id="map_enemy_visual_template" type="text/x-handlebars-template">
        <div style="position: relative;">
            <div class="modifier modifier_0 @{{modifier_0_classes}}" style="display: none;">
                @{{{modifier_0_html}}}
            </div>
            <div class="modifier modifier_1 @{{modifier_1_classes}}" style="display: none;">
                @{{{modifier_1_html}}}
            </div>
            <div class="modifier modifier_2 @{{modifier_2_classes}}" style="display: none;">
                @{{{modifier_2_html}}}
            </div>
            <div class="@{{selection_classes_base}} @{{selection_classes}}">
                <div class="@{{main_visual_classes}}">
                    @{{{main_visual_html}}}
                </div>
            </div>
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
    @else
        @php($factions = ['any' => __('Any'), 'alliance' => __('Alliance'), 'horde' => __('Horde')])
        <script id="enemy_pack_edit_popup_template" type="text/x-handlebars-template">
            <div id="enemy_pack_edit_popup_inner" class="popupCustom">
                <div class="form-group">
                    <label for="enemy_pack_edit_popup_faction_@{{id}}">{{ __('Faction') }}</label>
                    <select data-live-search="true" id="enemy_pack_edit_popup_faction_@{{id}}"
                            name="enemy_pack_edit_popup_faction_@{{id}}"
                            class="selectpicker popup_select" data-width="300px">
                        @foreach($factions as $key => $faction)
                            <option value="{{ $key }}">{{ $faction }}</option>
                        @endforeach
                    </select>
                </div>
                {!! Form::button(__('Submit'), ['id' => 'enemy_pack_edit_popup_submit_@{{id}}', 'class' => 'btn btn-info']) !!}
            </div>
        </script>

        <script id="enemy_patrol_edit_popup_template" type="text/x-handlebars-template">
            <div id="enemy_patrol_edit_popup_inner" class="popupCustom">
                <div class="form-group">
                    <label for="enemy_patrol_edit_popup_faction_@{{id}}">{{ __('Faction') }}</label>
                    <select data-live-search="true" id="enemy_patrol_edit_popup_faction_@{{id}}"
                            name="enemy_patrol_edit_popup_faction_@{{id}}"
                            class="selectpicker popup_select" data-width="300px">
                        @foreach($factions as $key => $faction)
                            <option value="{{ $key }}">{{ $faction }}</option>
                        @endforeach
                    </select>
                </div>
                {!! Form::button(__('Submit'), ['id' => 'enemy_patrol_edit_popup_submit_@{{id}}', 'class' => 'btn btn-info']) !!}
            </div>
        </script>

        <script id="enemy_edit_popup_template" type="text/x-handlebars-template">
            <div id="enemy_edit_popup_inner" class="popupCustom">
                <div class="form-group">
                    <label for="enemy_edit_popup_teeming_@{{id}}">{{ __('Teeming') }}</label>
                    <select data-live-search="true" id="enemy_edit_popup_teeming_@{{id}}"
                            name="enemy_edit_popup_teeming_@{{id}}"
                            class="selectpicker popup_select" data-width="300px">
                        <option value="">{{ __('Always visible') }}</option>
                        <option value="visible">{{ __('Visible when Teeming only') }}</option>
                        <option value="hidden">{{ __('Hidden when Teeming only') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="enemy_edit_popup_faction_@{{id}}">{{ __('Faction') }}</label>
                    <select data-live-search="true" id="enemy_edit_popup_faction_@{{id}}"
                            name="enemy_edit_popup_faction_@{{id}}"
                            class="selectpicker popup_select" data-width="300px">
                        @foreach($factions as $key => $faction)
                            <option value="{{ $key }}">{{ $faction }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="enemy_edit_popup_enemy_forces_override_@{{id}}">{{ __('Enemy forces (override, -1 to inherit)') }}</label>
                    {!! Form::text('enemy_edit_popup_enemy_forces_override_@{{id}}', null,
                                    ['id' => 'enemy_edit_popup_enemy_forces_override_@{{id}}', 'class' => 'form-control']) !!}
                </div>
                <div class="form-group">
                    <label for="enemy_edit_popup_npc_@{{id}}">{{ __('NPC') }}</label>
                    <select data-live-search="true" id="enemy_edit_popup_npc_@{{id}}"
                            name="enemy_edit_popup_npc_@{{id}}"
                            class="selectpicker popup_select" data-width="300px">
                        @foreach($npcs as $npc)
                            <option value="{{$npc->id}}">{{ sprintf("%s (%s)", $npc->name, $npc->id) }}</option>
                        @endforeach
                    </select>
                </div>
                {!! Form::button(__('Submit'), ['id' => 'enemy_edit_popup_submit_@{{id}}', 'class' => 'btn btn-info']) !!}
            </div>
        </script>

        <script id="dungeon_floor_switch_edit_popup_template" type="text/x-handlebars-template">
            <div id="dungeon_floor_switch_edit_popup_inner" class="popupCustom">
                <div class="form-group">
                    <label for="dungeon_floor_switch_edit_popup_target_floor">{{ __('Connected floor') }}</label>
                    <select id="dungeon_floor_switch_edit_popup_target_floor"
                            name="dungeon_floor_switch_edit_popup_target_floor"
                            class="selectpicker dungeon_floor_switch_edit_popup_target_floor" data-width="300px">
                        @{{#floors}}
                        <option value="@{{id}}">@{{name}}</option>
                        @{{/floors}}
                    </select>
                </div>
                {!! Form::button(__('Submit'), ['id' => 'dungeon_floor_switch_edit_popup_submit', 'class' => 'btn btn-info']) !!}
            </div>
        </script>
    @endif
@endsection

<div id="map" class="virtual-tour-element"
     data-position="auto">

</div>

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