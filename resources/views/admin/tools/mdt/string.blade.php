<?php
$asDungeonroute = isset($asDungeonroute) ? $asDungeonroute : false;
?>

@extends('layouts.sitepage', ['showAds' => false, 'title' => __('View MDT String')])

@section('header-title', __('View MDT String contents'))

@section('content')
    {{ Form::open(['route' => $asDungeonroute ? 'admin.tools.mdt.string.viewasdungeonroute.submit' : 'admin.tools.mdt.string.submit']) }}
    <div class="form-group">
        {!! Form::label('import_string', __('Paste your Mythic Dungeon Tools export string')) !!}
        {{ Form::textarea('import_string', '', ['class' => 'form-control', 'data-simplebar' => '']) }}
    </div>
    <div class="form-group">
        {!! Form::submit(__('Submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection