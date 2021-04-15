<?php
$dungeonroute = isset($dungeonroute) ? $dungeonroute : false;
?>

@extends('layouts.sitepage', ['showAds' => false, 'title' => __('View dungeonroute as MDT String')])

@section('header-title', __('View dungeonroute as MDT String'))

@section('content')
    {{ Form::open(['route' => 'admin.tools.mdt.dungeonroute.viewasstring.submit']) }}
    <div class="form-group">
        {!! Form::label('public_key', __('Dungeonroute public key')) !!}
        {{ Form::text('public_key', '', ['class' => 'form-control', 'data-simplebar' => '']) }}
    </div>
    <div class="form-group">
        {!! Form::submit(__('Submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection