<?php
/** @var \App\Models\Release $release */
$changelog = isset($release) ? $release->changelog : new \App\Models\ReleaseChangelog();
?>

@extends('layouts.sitepage', ['breadcrumbsParams' => [$release ?? null], 'showAds' => false, 'title' => $headerTitle])
@section('header-title', __('New release'))
@include('common.general.inline', ['path' => 'admin/release/edit', 'options' => ['changelog' => $changelog, 'categories' => $categories]])

@section('content')
    @isset($release)
        {{ Form::model($release, ['route' => ['admin.release.update', $release->version], 'method' => 'patch', 'files' => true]) }}
    @else
        {{ Form::open(['route' => 'admin.release.savenew', 'files' => true]) }}
    @endisset

    <div class="form-group{{ $errors->has('version') ? ' has-error' : '' }}">
        {!! Form::label('version', __('Version')) !!}
        {!! Form::text('version', null, ['class' => 'form-control']) !!}
    </div>

    <div class="form-group{{ $errors->has('silent') ? ' has-error' : '' }}">
        {!! Form::label('silent', __('Silent')) !!}
        {!! Form::checkbox('silent', 1, isset($release) ? $release->silent : 0, ['class' => 'form-control left_checkbox']) !!}
    </div>

    <div class="form-group{{ $errors->has('spotlight') ? ' has-error' : '' }}">
        {!! Form::label('spotlight', __('Spotlight')) !!}
        {!! Form::checkbox('spotlight', 1, isset($release) ? $release->spotlight : 0, ['class' => 'form-control left_checkbox']) !!}
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

    {!! Form::submit(isset($release) ? __('Edit') : __('Submit'), ['class' => 'btn btn-info']) !!}

    {!! Form::close() !!}
@endsection
