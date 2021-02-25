<?php
/** @var \App\Models\Release $model */
$changelog = isset($model) ? $model->changelog : new \App\Models\ReleaseChangelog();
?>

@extends('layouts.sitepage', ['showAds' => false, 'title' => $headerTitle])
@section('header-title', __('View releases'))
@section('header-addition')
    <a href="{{ route('admin.releases') }}" class="btn btn-info text-white float-right" role="button">
        <i class="fas fa-backward"></i> {{ __('Release list') }}
    </a>
@endsection
@include('common.general.inline', ['path' => 'admin/release/edit', 'options' => ['changelog' => $changelog, 'categories' => $categories]])

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['admin.release.update', $model->version], 'method' => 'patch', 'files' => true]) }}
    @else
        {{ Form::open(['route' => 'admin.release.savenew', 'files' => true]) }}
    @endisset

    <div class="form-group{{ $errors->has('version') ? ' has-error' : '' }}">
        {!! Form::label('version', __('Version')) !!}
        {!! Form::text('version', null, ['class' => 'form-control']) !!}
    </div>

    <div class="form-group{{ $errors->has('silent') ? ' has-error' : '' }}">
        {!! Form::label('silent', __('Silent')) !!}
        {!! Form::checkbox('silent', 1, isset($model) ? $model->silent : 0, ['class' => 'form-control left_checkbox']) !!}
    </div>

    <h4>{{ __('Changelog') }}</h4>

    <div class="form-group{{ $errors->has('description') ? ' has-error' : '' }}">
        {!! Form::label('changelog_description', __('Description')) !!}
        {!! Form::text('changelog_description', $changelog->description, ['class' => 'form-control']) !!}
    </div>

    <div id="changes_container" class="mb-4">
        <div class="row">
            <div class="col-2">
                {{ __('Ticket nr.') }}
            </div>
            <div class="col-10">
                {{ __('Change') }}
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col">
            <button id="add_change_button" class="btn btn-success" type="button">
                <i class="fas fa-plus"></i> {{ __('Add change') }}
            </button>
        </div>
    </div>

    {!! Form::submit(isset($model) ? __('Edit') : __('Submit'), ['class' => 'btn btn-info']) !!}

    {!! Form::close() !!}
@endsection
