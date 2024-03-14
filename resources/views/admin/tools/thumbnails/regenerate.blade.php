@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.thumbnails.regenerate.title')])

@section('header-title', __('view_admin.tools.thumbnails.regenerate.header'))

@section('content')
    {{ Form::open(['route' => 'admin.tools.thumbnails.regenerate.submit']) }}
    <div class="form-group">
        @include('common.dungeon.select', ['activeOnly' => false])
    </div>
    <div class="form-group">
        {!! Form::label('truesight', __('view_admin.tools.thumbnails.regenerate.only_missing')) !!}
        {!! Form::checkbox('only_missing', 1, false, ['class' => 'form-control left_checkbox']) !!}
    </div>
    <div class="form-group">
        {!! Form::submit(__('view_admin.tools.thumbnails.regenerate.submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection
