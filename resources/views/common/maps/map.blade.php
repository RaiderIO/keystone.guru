<?php
$isAdmin = isset($admin) && $admin;
/** @var App\Models\Dungeon $dungeon */
/** @var App\Models\DungeonRoute $dungeonroute */
// Enabled by default if it's not set, but may be explicitly disabled
// Do not show if it does not make sense (only one floor)
$floorSelection = (!isset($floorSelect) || $floorSelect) && $dungeon->floors->count() !== 1;
$edit = isset($edit) && $edit ? 'true' : 'false';
$routePublicKey = isset($dungeonroute) ? $dungeonroute->public_key : '';
$routeEnemyForces = isset($dungeonroute) ? $dungeonroute->enemy_forces : 0;
// For Siege of Boralus
$routeFaction = isset($dungeonroute) ? strtolower($dungeonroute->faction->name) : 'any';
$teeming = isset($dungeonroute) ? $dungeonroute->teeming : false;

$introTexts = [
    __('If your dungeon has multiple floors, this is where you can change floors. You can also click the doors on the map to go to the next floor.'),
    __('This is the dungeon map. This is where you see an overview of enemies, which packs they may belong to, any patrols and your own planning. Click
    on enemies to assign them a raid marker, hover over them to see quick details about the enemy.'),
    __('These are the map controls where you can control the map and interact with it.'),
    __('You can use these controls to zoom the map in or out. You can also use ctrl + scrollwheel if you\'re on a computer'),
    __('These are your drawing tools.'),
    __('You can draw routes with this tool. Click it, then draw a route (a line) from A to B, with as many points are you like. Once finished, you can click
    the line on the map to change its color. You can add as many routes as you want, use the colors to your advantage. Color the line yellow for Rogue Shrouding,
    or purple for a Warlock Gateway, for example.'),
    __('This is a \'kill zone\'. You use these zones to indicate what enemies you are killing, and most importantly, where. Place a zone on the map and click it again.
    You can then select any enemy on the map that has not already \'been killed\' by another kill zone. When you select a pack, you automatically select all enemies in the pack.
    Once you have selected enemies your enemy forces (top right) will update to reflect your new enemy forces counter.'),
    __('Use this control to place comments on the map, for example to indicate you\'re skipping a patrol or to indicate details and background info in your route.'),
    __('This is the edit button. You can use it to adjust your created routes, move your killzones or comments.'),
    __('This is the delete button. Click it once, then select the controls you wish to delete. Deleting happens in a preview mode, you have to confirm your delete in a label
    that pops up once you press the button. You can then confirm or cancel your staged changes. If you confirm the deletion, there is no turning back!'),
    __('This label indicates your current progress with enemy forces. Remember to use killzones to mark an enemy as killed and see this label updated.'),
    __('These are your visibility toggles. You can hide enemies, enemy patrols, enemy packs, your own routes, your own killzones, all map comments, start markers and floor switch markers.')
];
?>

@section('head')
    {{-- Make sure we don't override the head of the page this thing is included in --}}
    @parent

    <style>
        /* css to customize Leaflet default styles  */
        .popupCustom .leaflet-popup-tip,
        .popupCustom .leaflet-popup-content-wrapper {
            background: #e0e0e0;
            color: #234c5e;
        }

        .popup_select {
            width: 300px;
        }

        #map_controls .map_controls_custom,
        #map_faction_display_controls .map_controls_custom {
            width: 50px;
            background-image: none;
        }

        .map_enemy_tooltip {
            width: 240px;
            white-space: normal;
        }

        .leaflet-container {
            background-color: #2B3E50;
        }
    </style>
@endsection

@section('scripts')
    {{-- Make sure we don't override the scripts of the page this thing is included in --}}
    @parent

    <script>
        <?php // @TODO Convert this to an object to pass to the DungeonMap instead of multiple parameters? ?>
        // Data of the dungeon(s) we're selecting in the map
        let _dungeonData = {!! $dungeon !!};
        let _switchDungeonFloorSelect = "#map_floor_selection";
        let dungeonRoutePublicKey = '{{ $routePublicKey }}';
        let dungeonRouteEnemyForces = {{ $routeEnemyForces }};
        let dungeonRouteFaction = '{{ $routeFaction }}';

        let dungeonMap;

        $(function () {

            @if(!isset($manualInit) || !$manualInit)
            // Make sure that the select options have a valid value
            _refreshFloorSelect();

            @isset($selectedFloorId)
            $(_switchDungeonFloorSelect).val({{$selectedFloorId}});
            @endisset

                    @if($isAdmin)
                dungeonMap = new AdminDungeonMap('map', _dungeonData, $(_switchDungeonFloorSelect).val(), {{ $edit }});
            @else
                dungeonMap = new DungeonMap('map', _dungeonData, $(_switchDungeonFloorSelect).val(), {{ $edit }}, {{ $teeming }});
            @endif
            @endif

            $(_switchDungeonFloorSelect).change(function () {
                // Pass the new floor ID to the map
                dungeonMap.currentFloorId = $(_switchDungeonFloorSelect).val();
                dungeonMap.refreshLeafletMap();
            });

            $('#start_virtual_tour').bind('click', function () {
                console.log('starting virtual tour!');
                introjs().start();
            });

            // Bind leaflet virtual tour classes
            let selectors = [
                ['.leaflet-top.leaflet-left', 'right'],
                ['.leaflet-control-zoom.leaflet-bar.leaflet-control', 'right'],
                ['.leaflet-left .leaflet-draw-toolbar.leaflet-bar.leaflet-draw-toolbar-top', 'right'],
                ['.leaflet-draw-draw-route', 'right'],
                ['.leaflet-draw-draw-killzone', 'right'],
                ['.leaflet-draw-draw-mapcomment', 'right'],
                ['.leaflet-draw-edit-edit', 'right'],
                ['.leaflet-draw-edit-remove', 'right'],
                ['#map_enemy_forces', 'left'],
                ['.leaflet-right .leaflet-draw-toolbar.leaflet-bar.leaflet-draw-toolbar-top', 'left'],
            ];
            let texts = {!! json_encode($introTexts) !!};
            let offset = 2;

            for (let i = 0; i < selectors.length; i++) {
                let $selector = $(selectors[i][0]);
                $selector.attr('data-intro', texts[i + offset]);
                $selector.attr('data-position', selectors[i][1]);
                $selector.attr('data-step', i + (offset + 1));
            }
        });

        /**
         * Refreshes the floor select and fills it with the floors that fit the currently selected dungeon.
         * @private
         */
        function _refreshFloorSelect() {
            let $switchDungeonFloorSelect = $(_switchDungeonFloorSelect);
            if ($switchDungeonFloorSelect.is("select")) {
                // Clear of all options
                $switchDungeonFloorSelect.find('option').remove();
                // Add each new floor to the select
                $.each(_dungeonData.floors, function (index, floor) {
                    // Reconstruct the dungeon floor select
                    $switchDungeonFloorSelect.append($('<option>', {
                        text: floor.name,
                        value: floor.id
                    }));
                });
            }
        }
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

    <script id="map_enemy_forces_template" type="text/x-handlebars-template">
        <div id="map_enemy_forces" class="leaflet-draw-section">
            {{ __('Enemy forces')}}:
            <span id="map_enemy_forces_numbers">
                <span id="map_enemy_forces_count">0</span>/@{{ enemy_forces_total }}
                (<span id="map_enemy_forces_percent">0</span>%)
            </span>
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
            @if($isAdmin)
                <div class="row">
                    <div class="col-5 no-gutters">{{ __('Pack') }} </div>
                    <div class="col-7 no-gutters">@{{ attached_to_pack }}</div>
                </div>
            @endif
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
                            <div class="col map_route_edit_popup_class_color border-dark"
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
                            <div id="raid_marker_{{ $raidMarker->name }}"
                                 class="raid_marker_icon raid_marker_icon_{{ $raidMarker->name }}"
                                 data-name="{{ $raidMarker->name }}">
                            </div>
                            @if($i % 4 === 3)
                        </div>
                    @endif
                @endfor
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

<div class="container">
    <?php
    if (!isset($model) && $edit) {
    ?>
    <div class="form-group">
        <h4>
            {{ __('Unsure of what you\'re supposed to do?') }}
        </h4>
        <div id="start_virtual_tour" class="btn btn-info">
            <i class="fas fa-info-circle"></i> {{ __('Start virtual tour') }}
        </div>
    </div>
    <div class="form-group">
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i> {{ __('Warning! Any modification you make in tryout mode will not be saved!') }}
        </div>
    </div>
    <?php } else { ?>
    <div class="form-group">
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i> {{ __('Mapping data is a work in progress. Please err on the side of overpulling over exact 100% while I correct any reported mistakes.') }}
        </div>
    </div>
    <?php } ?>
    @if($floorSelection)
        <div class="form-group virtual-tour-element" data-intro="{{ $introTexts[0] }}" data-step="1"
             data-position="bottom-middle-aligned">
            {!! Form::label('map_floor_selection', __('Select floor')) !!}
            {!! Form::select('map_floor_selection', [], 1, ['class' => 'form-control']) !!}
        </div>
    @else
        {!! Form::input('hidden', 'map_floor_selection', $dungeon->floors[0]->id, ['id' => 'map_floor_selection']) !!}
    @endif
</div>

<div class="form-group">
    <div id="map" class="col-md-12 virtual-tour-element" data-intro="{{ $introTexts[1] }}" data-step="2"
         data-position="auto"></div>
</div>