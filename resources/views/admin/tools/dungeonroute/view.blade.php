@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.dungeonroute.view.title')])

@section('header-title', __('view_admin.tools.dungeonroute.view.header'))

@section('content')
    {{ Form::open(['route' => 'admin.tools.dungeonroute.view.submit']) }}
    <div class="form-group">
        {!! Form::label('public_key', __('view_admin.tools.dungeonroute.view.public_key')) !!}
        {{ Form::text('public_key', '', ['class' => 'form-control', 'data-simplebar' => '']) }}
    </div>
    <div class="form-group">
        {!! Form::submit(__('view_admin.tools.dungeonroute.view.submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection
