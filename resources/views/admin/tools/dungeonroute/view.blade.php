@extends('layouts.sitepage', ['showAds' => false, 'title' => __('View dungeonroute')])

@section('header-title', __('View dungeonroute'))

@section('content')
    {{ Form::open(['route' => 'admin.tools.dungeonroute.view.submit']) }}
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