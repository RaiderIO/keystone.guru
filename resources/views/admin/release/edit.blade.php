<?php

use App\Models\Release;
use App\Models\ReleaseChangelog;
use App\Models\ReleaseChangelogCategory;
use Illuminate\Support\Collection;

/**
 * @var Release                               $release
 * @var Collection<ReleaseChangelogCategory> $categories
 */

$changelog = isset($release) ? $release->changelog : new ReleaseChangelog();
?>

@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$release ?? null],
    'showAds' => false,
    'title' => isset($release) ? __('view_admin.release.edit.title_edit') : __('view_admin.release.edit.title_new')
])
@section('header-title', isset($release) ? __('view_admin.release.edit.header_edit') : __('view_admin.release.edit.header_new'))
@include('common.general.inline', ['path' => 'admin/release/edit', 'options' => ['changelog' => $changelog, 'categories' => $categories]])

@section('content')
    @isset($release)
        {{ html()->modelForm($release, 'PATCH', route('admin.release.update', $release->version))->acceptsFiles()->open() }}
    @else
        {{ html()->form('POST', route('admin.release.savenew'))->acceptsFiles()->open() }}
    @endisset

    <div class="form-group{{ $errors->has('version') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.release.edit.version'), 'version') }}
        {{ html()->text('version')->class('form-control') }}
    </div>

    <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.release.edit.title'), 'title') }}
        {{ html()->text('title')->class('form-control') }}
    </div>

    <div class="row form-group">
        <div class="col {{ $errors->has('backup_db') ? 'has-error' : '' }}">
            {{ html()->label(__('view_admin.release.edit.backup_db'), 'backup_db') }}
            {{ html()->checkbox('backup_db', isset($release) ? $release->backup_db : 1, 1)->class('form-control left_checkbox') }}
        </div>

        <div class="col {{ $errors->has('silent') ? 'has-error' : '' }}">
            {{ html()->label(__('view_admin.release.edit.silent'), 'silent') }}
            {{ html()->checkbox('silent', isset($release) ? $release->silent : 0, 1)->class('form-control left_checkbox') }}
        </div>

        <div class="col {{ $errors->has('spotlight') ? 'has-error' : '' }}">
            {{ html()->label(__('view_admin.release.edit.spotlight'), 'spotlight') }}
            {{ html()->checkbox('spotlight', isset($release) ? $release->spotlight : 0, 1)->class('form-control left_checkbox') }}
        </div>

        <div class="col {{ $errors->has('released') ? 'has-error' : '' }}">
            {{ html()->label(__('view_admin.release.edit.released'), 'released') }}
            {{ html()->checkbox('released', isset($release) ? $release->released : 0, 1)->class('form-control left_checkbox')->disabled() }}
        </div>
    </div>

    <h4>{{ __('view_admin.release.edit.changelog') }}</h4>

    <div class="form-group{{ $errors->has('description') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.release.edit.description'), 'changelog_description') }}
        {{ html()->text('changelog_description', $changelog->description)->class('form-control') }}
    </div>

    <div id="changes_container" class="form-group mb-4">
        <div class="row">
            <div class="col-2">
                {{ __('view_admin.release.edit.ticket_nr') }}
            </div>
            <div class="col-10">
                {{ __('view_admin.release.edit.change') }}
            </div>
        </div>
    </div>

    <div class="form-group">
        <button id="add_change_button" class="btn btn-success" type="button">
            <i class="fas fa-plus"></i> {{ __('view_admin.release.edit.add_change') }}
        </button>
    </div>

    <div class="form-group">
        {{ html()->input('submit')->value(isset($release) ? __('view_admin.release.edit.edit') : __('view_admin.release.edit.submit'))->class('btn btn-info') }}
    </div>

    {{ html()->closeModelForm() }}


    @isset($release)
        <div class="form-group">
                <?php
                $releaseArr = $release->toArray();
                ?>
            {{ html()->label(__('view_admin.release.edit.release_json'), 'release_json') }}
            {{ html()->textarea('release_json', json_encode($releaseArr, JSON_PRETTY_PRINT))->class('form-control w-100') }}
        </div>
    @endisset
@endsection
