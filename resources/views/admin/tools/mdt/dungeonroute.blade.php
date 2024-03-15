@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.mdt.dungeonroute.title')])

@section('header-title', __('view_admin.tools.mdt.dungeonroute.header'))

@section('content')
    {{ Form::open(['route' => 'admin.tools.mdt.dungeonroute.viewasstring.submit']) }}
    <div class="form-group">
        {!! Form::label('public_key', __('view_admin.tools.mdt.dungeonroute.public_key')) !!}
        {{ Form::text('public_key', '', ['class' => 'form-control', 'data-simplebar' => '']) }}
    </div>
    <div class="form-group">
        {!! Form::submit(__('view_admin.tools.mdt.dungeonroute.submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection
