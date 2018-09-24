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
                @foreach(\App\Models\Faction::where('name', '<>', 'Unspecified')->get() as $faction)
                    <a class="map_faction_display_control map_controls_custom" href="#"
                       data-faction="{{ strtolower($faction->name) }}"
                       title="{{ $faction->name }}">
                        <i class="fas fa-check-square checkbox"
                           style="width: 15px"></i>
                        <img src="{{ $faction->iconfile->icon_url }}" class="select_icon faction_icon"
                             data-toggle="tooltip" title="{{ $faction->name }}"/>
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
                @if($admin)
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
    @if($floorSelection)
        <div class="form-group">
            {!! Form::label('map_floor_selection', __('Select floor')) !!}
            {!! Form::select('map_floor_selection', [], 1, ['class' => 'form-control']) !!}
        </div>
    @else
        {!! Form::input('hidden', 'map_floor_selection', $dungeon->floors[0]->id, ['id' => 'map_floor_selection']) !!}
    @endif
</div>

<div class="form-group">
    <div id="map" class="col-md-12"></div>
</div>