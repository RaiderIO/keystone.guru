<?php
/** @var \App\Models\Release $release */
/** @var \App\Models\ReleaseChangelogCategory[]|\Illuminate\Support\Collection $categories */
$changelog = isset($release) ? $release->changelog : new \App\Models\ReleaseChangelog();
?>

@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$release ?? null],
    'showAds' => false,
    'title' => isset($release) ? __('views/admin.release.edit.title_edit') : __('views/admin.release.edit.title_new')
])
@section('header-title', isset($release) ? __('views/admin.release.edit.header_edit') : __('views/admin.release.edit.header_new'))
@include('common.general.inline', ['path' => 'admin/release/edit', 'options' => ['changelog' => $changelog, 'categories' => $categories]])

@section('content')
    @isset($release)
        {{ Form::model($release, ['route' => ['admin.release.update', $release->version], 'method' => 'patch', 'files' => true]) }}
    @else
        {{ Form::open(['route' => 'admin.release.savenew', 'files' => true]) }}
    @endisset

    <div class="form-group{{ $errors->has('version') ? ' has-error' : '' }}">
        {!! Form::label('version', __('views/admin.release.edit.version')) !!}
        {!! Form::text('version', null, ['class' => 'form-control']) !!}
    </div>

    <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">
        {!! Form::label('title', __('views/admin.release.edit.title')) !!}
        {!! Form::text('title', null, ['class' => 'form-control']) !!}
    </div>

    <div class="form-group{{ $errors->has('silent') ? ' has-error' : '' }}">
        {!! Form::label('silent', __('views/admin.release.edit.silent')) !!}
        {!! Form::checkbox('silent', 1, isset($release) ? $release->silent : 0, ['class' => 'form-control left_checkbox']) !!}
    </div>

    <div class="form-group{{ $errors->has('spotlight') ? ' has-error' : '' }}">
        {!! Form::label('spotlight', __('views/admin.release.edit.spotlight')) !!}
        {!! Form::checkbox('spotlight', 1, isset($release) ? $release->spotlight : 0, ['class' => 'form-control left_checkbox']) !!}
    </div>

    <h4>{{ __('views/admin.release.edit.changelog') }}</h4>

    <div class="form-group{{ $errors->has('description') ? ' has-error' : '' }}">
        {!! Form::label('changelog_description', __('views/admin.release.edit.description')) !!}
        {!! Form::text('changelog_description', $changelog->description, ['class' => 'form-control']) !!}
    </div>

    <div id="changes_container" class="form-group mb-4">
        <div class="row">
            <div class="col-2">
                {{ __('views/admin.release.edit.ticket_nr') }}
            </div>
            <div class="col-10">
                {{ __('views/admin.release.edit.change') }}
            </div>
        </div>
    </div>

    <div class="form-group">
        <button id="add_change_button" class="btn btn-success" type="button">
            <i class="fas fa-plus"></i> {{ __('views/admin.release.edit.add_change') }}
        </button>
    </div>

    <div class="form-group">
        {!! Form::submit(isset($release) ? __('views/admin.release.edit.edit') : __('views/admin.release.edit.submit'), ['class' => 'btn btn-info']) !!}
    </div>

    {!! Form::close() !!}


    @isset($release)
        <div class="form-group">
            {!! Form::label('release_json', __('views/admin.release.edit.release_json')) !!}
            {!! Form::textarea('release_json', json_encode($release, JSON_PRETTY_PRINT), ['class' => 'form-control w-100']) !!}
        </div>
    @endisset
@endsection
