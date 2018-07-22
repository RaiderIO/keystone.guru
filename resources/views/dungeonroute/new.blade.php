@extends('layouts.app', ['wide' => true])
@section('header-title', $headerTitle)

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['dungeonroute.update', $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => 'dungeonroute.savenew']) }}
    @endisset
    <div id="setup_container" class="container {{ isset($model) ? 'hidden' : '' }}">
        <h3>
            {{ __('General') }}
        </h3>
        <div class="form-group">
            {!! Form::label('title', __('Title') . "*") !!}
            {!! Form::text('title', '', ['class' => 'form-control']) !!}
        </div>
        <div class="form-group">
            {!! Form::label('dungeon', __('Select dungeon') . "*") !!}
            {!! Form::select('dungeon', \App\Models\Dungeon::all()->pluck('name', 'id'), 0, ['class' => 'form-control']) !!}
        </div>
        @include('common.group.composition')

        <div class="col-lg-12">
            <div class="form-group">
                {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}
            </div>
        </div>
    </div>

    {!! Form::close() !!}
@endsection

