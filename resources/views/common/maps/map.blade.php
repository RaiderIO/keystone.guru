<?php

$isAdmin = isset($admin) && $admin;
/** @var App\Models\Dungeon $dungeon */
// Enabled by default if it's not set, but may be explicitly disabled
// Do not show if it does not make sense (only one floor)
$floorSelection = (!isset($floorSelect) || $floorSelect) && $dungeon->floors->count() !== 1;
$edit = isset($edit) && $edit ? 'true' : 'false';
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

        .enemy_edit_popup_npc {
            width: 300px;
        }

        .enemy_edit_popup_teeming {
            width: 300px;
        }

        #map_controls .map_controls_custom {
            width: 50px;
            background-image: none;
        }

        #map_controls .map_controls_custom {
            width: 50px;
            background-image: none;
        }
    </style>
@endsection

@section('scripts')
    {{-- Make sure we don't override the scripts of the page this thing is included in --}}
    @parent

    <script>
        // Data of the dungeon(s) we're selecting in the map
        let _dungeonData = {!! $dungeon !!};
        let _switchDungeonFloorSelect = "#map_floor_selection";

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
                dungeonMap = new DungeonMap('map', _dungeonData, $(_switchDungeonFloorSelect).val(), {{ $edit }});
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

    @if($isAdmin)
        <script id="enemy_edit_popup_template" type="text/x-handlebars-template">
            <div id="enemy_edit_popup_inner" class="popupCustom">
                <div class="form-group">
                    <span>{{ __('Attached to pack:') }}</span> <span id="enemy_edit_popup_attached_to_pack_@{{id}}">false</span>
                </div>
                <div class="form-group">
                    <label for="enemy_edit_popup_teeming_@{{id}}">{{ __('Teeming') }}</label>
                    <select data-live-search="true" id="enemy_edit_popup_teeming_@{{id}}" name="enemy_edit_popup_teeming_@{{id}}"
                            class="selectpicker enemy_edit_popup_teeming" data-width="300px">
                        <option value="">{{ __('Always visible') }}</option>
                        <option value="visible">{{ __('Visible when Teeming only') }}</option>
                        <option value="hidden">{{ __('Hidden when Teeming only') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="enemy_edit_popup_npc_@{{id}}">{{ __('NPC') }}</label>
                    <select data-live-search="true" id="enemy_edit_popup_npc_@{{id}}" name="enemy_edit_popup_npc_@{{id}}"
                            class="selectpicker enemy_edit_popup_npc" data-width="300px">
                        @foreach($npcs as $npc)
                            <option value="{{$npc->id}}">{{ sprintf("%s (%s)", $npc->name, $npc->id) }}</option>
                        @endforeach
                    </select>
                </div>
                {!! Form::button(__('Submit'), ['id' => 'enemy_edit_popup_submit', 'class' => 'btn btn-info']) !!}
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