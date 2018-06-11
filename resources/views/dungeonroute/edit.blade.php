@extends('layouts.app', ['wide' => true])
@section('header-title', $headerTitle)

@section('scripts')
    <script>
        $(function(){
            #("#next_to_map_btn").bind('click', function(){

            });
        })
    </script>
@endsection

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['dungeonroute.update', $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => 'dungeonroute.savenew', 'files' => true]) }}
    @endisset
    <div id="setup_container" class="container">
        <div class="form-group">
            {!! Form::label('dungeon_selection', __('Select dungeon')) !!}
            {!! Form::select('dungeon_selection', \App\Models\Dungeon::all()->pluck('name', 'id'), 0, ['class' => 'form-control']) !!}
        </div>
        {!! Form::button('<i class="fa fa-forward"></i> ' . __('Next'), ['id' => 'next_to_map_btn', 'class' => 'btn btn-info']) !!}
    </div>

    <div id="map_container" class="invisible">
        @include('common.maps.map', ['admin' => false, 'dungeons' => \App\Models\Dungeon::all()])

        {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}
    </div>

    {!! Form::close() !!}
@endsection

