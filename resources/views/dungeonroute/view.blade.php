@extends('layouts.app', ['wide' => true])
@section('header-title', $model->title)

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['dungeonroute.update', $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => 'dungeonroute.savenew']) }}
    @endisset

    @isset($model)
        <div class="col-lg-12">
            <div id="map_container">
                @include('common.maps.map', [
                    'dungeon' => \App\Models\Dungeon::findOrFail($model->dungeon_id),
                    'edit' => false
                ])
            </div>
        </div>
    @endisset

    {!! Form::close() !!}
@endsection

