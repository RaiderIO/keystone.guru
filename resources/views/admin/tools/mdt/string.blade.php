<?php
$asDungeonroute = $asDungeonroute ?? false;
?>

@extends('layouts.sitepage', ['showAds' => false, 'title' => __('views/admin.tools.mdt.string.title')])

@section('header-title', __('views/admin.tools.mdt.string.header'))

@section('content')
    {{ Form::open(['route' => $asDungeonroute ? 'admin.tools.mdt.string.viewasdungeonroute.submit' : 'admin.tools.mdt.string.submit']) }}
    <div class="form-group">
        {!! Form::label('import_string', __('views/admin.tools.mdt.string.paste_your_mdt_export_string')) !!}
        {{ Form::textarea('import_string', '', ['class' => 'form-control', 'data-simplebar' => '']) }}
    </div>
    <div class="form-group">
        {!! Form::submit(__('views/admin.tools.mdt.string.submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection