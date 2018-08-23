@extends('layouts.app', ['wide' => true])

@section('header-title', __('Dump dungeon data'))

@section('content')

    {{ Form::open(['route' => 'admin.datadump.viewexporteddungeondata']) }}

    <div class="form-group">
        {!! Form::label('dungeon_id', __('Select dungeon') . "*") !!}
        {!! Form::select('dungeon_id', $dungeons->pluck('name', 'id'), null, ['class' => 'form-control']) !!}
    </div>

    {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}

    {!! Form::close() !!}
@endsection