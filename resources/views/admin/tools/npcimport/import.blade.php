@extends('layouts.app', ['showAds' => false, 'title' => __('Mass import NPCs')])

@section('header-title', __('Mass import NPCs'))

@section('content')
    {{ Form::open(['route' => 'admin.tools.npcimport.submit']) }}
    <div class="form-group">
        {!! Form::label('import_string', __('Paste the npc import string')) !!}
        {{ Form::textarea('import_string', '', ['class' => 'form-control', 'data-simplebar' => '']) }}
    </div>
    <div class="form-group">
        {!! Form::submit(__('Submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection