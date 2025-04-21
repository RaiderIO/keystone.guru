<?php
/**
 * @var string|null $messageBanner
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.messagebanner.set.title')])

@section('header-title', __('view_admin.tools.messagebanner.set.header'))

@section('content')
    {{ Form::open(['route' => 'admin.tools.messagebanner.set.submit']) }}
    <div class="form-group">
        {!! Form::label('message', __('view_admin.tools.messagebanner.set.message')) !!}
        {{ Form::textarea('message', $messageBanner ?? '', ['class' => 'form-control']) }}
    </div>
    <div class="form-group">
        {!! Form::submit(__('view_admin.tools.messagebanner.set.submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection
