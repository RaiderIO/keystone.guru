@extends('layouts.app', ['wide' => true])
@section('header-title', $headerTitle)

@section('head')
    @parent

    <style>
        #group_composition_toggle {
            cursor: pointer;
            border: #d3e0e9 solid 1px;

            -webkit-border-radius: 3px;
            -moz-border-radius: 3px;
            border-radius: 3px;
        }

        #map {
            margin-top: 10px;
            margin-bottom: 10px;
        }
    </style>
@endsection

@section('scripts')
    @parent

    <script>
        $(function () {
            let $groupComposition = $('#group_composition');
            $groupComposition.on('hide.bs.collapse', function (e) {
                let $caret = $("#group_composition_caret");
                $caret.removeClass('fa-caret-up');
                $caret.addClass('fa-caret-down');
            });

            $groupComposition.on('show.bs.collapse', function (e) {
                console.log()
                let $caret = $("#group_composition_caret");
                $caret.removeClass('fa-caret-down');
                $caret.addClass('fa-caret-up');
            });
        });
    </script>
@endsection

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['dungeonroute.update', $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => 'dungeonroute.savenew']) }}
    @endisset

    @isset($model)
        <div class="col-lg-12">
            <div id="map_container col-lg-12">
                @include('common.maps.map', [
                // Use findMany rather than findOrFail; we need a collection in this parameter
                    'dungeons' => \App\Models\Dungeon::findMany([$model->dungeon_id]),
                    'dungeonSelect' => false
                ])
            </div>

            <div id="group_composition_toggle" class="col-lg-12 text-center btn btn-default" data-toggle="collapse"
                 data-target="#group_composition">
                <h4>
                    Group composition <i id="group_composition_caret" class="fa fa-caret-down"></i>
                </h4>
            </div>

            <div id="group_composition" class="col-lg-12 collapse">
                @include('common.group.composition')
            </div>

        </div>
    @endisset

    {!! Form::close() !!}
@endsection

