<?php

$isAdmin = isset($admin) && $admin;
/** @var \Illuminate\Support\Collection $dungeons */
// Hide the floor selection if it's just one dungeon with no additional floors
$dungeonSelection = (!isset($dungeonSelect) || $dungeonSelect) && $dungeons->count() > 1;
// Enabled by default if it's not set, but may be explicitly disabled
// Do not show if it does not make sense (only one floor)
$floorSelection = (!isset($floorSelect) || $floorSelect) && !($dungeons->count() === 1 && $dungeons->first()->floors->count() === 1);
?>

@section('scripts')
    {{-- Make sure we don't override the scripts of the page this thing is included in --}}
    @parent

    <script>
                {{-- Always --}}

        var _dungeonConstruct = {
                    @foreach ($dungeons as $dungeon)
                            {{-- @var $dungeon \App\Models\Dungeon --}}
                    "{{ $dungeon->id }}": {
                        "key": "{{ strtolower(str_replace(" ", "", $dungeon->name)) }}",
                        "name": "{{ $dungeon->name }}",
                        "floors": {
                            @foreach ($dungeon->floors as $floor)
                            "{{ $floor->id }}": {
                                "index": "{{ $floor->index }}",
                                "name": "{{ $floor->name }}"
                            },
                            @endforeach
                        }
                    },
                    @endforeach
            };

        @if(!isset($manualInit) || !$manualInit)
        $(function () {
            updateDungeonSelection();
            updateFloorSelection();
            _refreshMap();
        });
        @endif

        function _refreshMap() {
            setCurrentMapName(getCurrentDungeon(), getCurrentFloor());
            refreshLeafletMap();
        }

        function getDungeonDataById(id){
            var result = false;
            $.each(_dungeonConstruct, function(key, value){
                if( key === id ){
                    result = value;
                    return false;
                }
            });
            return result;
        }

        function getCurrentDungeon() {
            {{-- Dynamic selection --}}
            @if($dungeonSelection)
                return $(_switchDungeonSelect).val();
            @else
            {{-- Hard coded dungeon name --}}
                return "{{ strtolower(str_replace(" ", "", $dungeons->first()->name)) }}";
            @endif
        }

        function getCurrentFloor() {
            {{-- Dynamic selection --}}
            @if($floorSelection)
                return $(_switchDungeonFloorSelect).val()
            @else
            {{-- Hard coded floor --}}
                return "1";
            @endif
        }

        function updateDungeonSelection() {
            @if($dungeonSelection)
            // Clear of all options
            $(_switchDungeonSelect).find('option').remove();
            // Add new ones
            $.each(_dungeonConstruct, function (id, dungeon) {
                $(_switchDungeonSelect).append($('<option>', {
                    text: dungeon.name,
                    value: dungeon.key
                }));
            });
            @endif
        }

        function updateFloorSelection() {
            @if($floorSelection)
            // Clear of all options
            $(_switchDungeonFloorSelect).find('option').remove();
            // Add new ones
            $.each(_dungeonConstruct, function (id, dungeon) {
                // Find the dungeon..
                if (dungeon.key === getCurrentDungeon()) {
                    // Add each new floor to the select
                    $.each(dungeon.floors, function (id, floor) {
                        $(_switchDungeonFloorSelect).append($('<option>', {
                            text: floor.name,
                            value: floor.index
                        }));
                    });
                }
            });
            @endif
        }

                {{--  Dungeon logic --}}
                @if($dungeonSelection)
        var _switchDungeonSelect = "#map_dungeon_selection";
        $(function () {
            $(_switchDungeonSelect).change(function () {
                updateFloorSelection();
                _refreshMap();
            });
        });

                @endif

                {{--  Floor logic --}}
                @if($floorSelection)
        var _switchDungeonFloorSelect = "#map_floor_selection";
        $(function () {
            $(_switchDungeonFloorSelect).change(function () {
                _refreshMap();
            });
        });

        @endif

        {{--  Only if admin --}}
        @if($isAdmin)

        $(function () {
            adminInitControls(mapObj);
        });
        @endif

    </script>
@endsection

<div class="container">
    {{-- Only show the dungeon selector when the amount of dungeons we want to show is greater than 1, otherwise just show the first --}}
    @if($dungeonSelection)
        <div class="form-group">
            {!! Form::label('map_dungeon_selection', __('Select dungeon')) !!}
            {!! Form::select('map_dungeon_selection', [], 0, ['class' => 'form-control']) !!}
        </div>
    @endif
    @if($floorSelection)
        <div class="form-group">
            {!! Form::label('map_floor_selection', __('Select floor')) !!}
            {!! Form::select('map_floor_selection', [], 1, ['class' => 'form-control']) !!}
        </div>
    @endif
</div>

<div class="form-group">
    <div id="map" class="col-md-{{ $isAdmin ? "10" : "12" }}"></div>
    @if($isAdmin)
        {{-- @include('common.maps.mapadmintools') -- }}
    @endif
</div>