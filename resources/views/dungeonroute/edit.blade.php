@extends('layouts.app', ['wide' => true])
@section('header-title', $headerTitle)

@section('head')
    <style>
        #map {
            height: 600px;
        }
    </style>
@endsection

@section('scripts')
    <script>
        var _switchDungeonSelect = "#switch_dungeon";
        var _switchDungeonFloorSelect = "#switch_dungeon_floor";

        $(function () {
            @foreach ($dungeons as $dungeon)
            <?php /* @var $dungeon \App\Models\Dungeon */ ?>

            $(_switchDungeonSelect).append($('<option>', {
                text: "{{ $dungeon->name }}",
                value: "{{ str_replace(" ", "", ($dungeon->name)) }}"
            }).data("floors", "1"));

            @endforeach

            $(_switchDungeonSelect).change(_dungeonChanged);

            $(_switchDungeonFloorSelect).change(function() {
                _refreshMap();
            });

            // Init
            _dungeonChanged();
        });

        function _dungeonChanged(){
            // Change the amount of floors this map has
            var selected = $(_switchDungeonSelect).find('option:selected');
            var floors = selected.data('floors');
            _setFloorCount(floors);

            // Refresh now
            _refreshMap();
        }

        function _refreshMap() {
            setCurrentMapName($(_switchDungeonSelect).val(), $(_switchDungeonFloorSelect).val());
        }

        function _setFloorCount(floors){
            $(_switchDungeonFloorSelect).empty();
            for(var i = 1; i <= floors; i++ ){
                $(_switchDungeonFloorSelect).append($('<option>', {
                    text: i,
                    value: i
                }));
            }
        }
    </script>
@endsection

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['dungeonroute.update', $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => 'dungeonroute.savenew', 'files' => true]) }}
    @endisset

{!! Form::submit('Submit', ['class' => 'btn btn-info']) !!}
<div>
    <select id="switch_dungeon"></select>
    <select id="switch_dungeon_floor"></select>
</div>
<div id="map" class="col-md-12"></div>
{!! Form::close() !!}
@endsection
