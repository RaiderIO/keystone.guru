<?php

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use Illuminate\Support\Collection;

/**
 * @var Dungeon                 $dungeon
 * @var Floor                   $floor
 * @var Collection              $availableKeysSelect
 */
?>

@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$dungeon ?? null],
    'showAds' => false,
    'title' => isset($dungeon) ? __('view_admin.dungeon.edit.title_edit') : __('view_admin.dungeon.edit.title_new'),
    ])

@section('header-title')
    {{ isset($dungeon) ? __('view_admin.dungeon.edit.header_edit') : __('view_admin.dungeon.edit.header_new') }}
@endsection

@section('content')
    <div class="mb-4">
        @isset($dungeon)
            {{ html()->modelForm($dungeon, 'PATCH', route('admin.dungeon.update', $dungeon->slug))->open() }}
        @else
            {{ html()->form('POST', route('admin.dungeon.savenew'))->open() }}
        @endisset

        <div class="row form-group">
            <div class="col {{ $errors->has('active') ? ' has-error' : '' }}">
                {{ html()->label(__('view_admin.dungeon.edit.active'), 'active') }}
                {{ html()->checkbox('active', $dungeon?->active ?? 1, 1)->class('form-control left_checkbox') }}
                @include('common.forms.form-error', ['key' => 'active'])
            </div>

            <div class="col {{ $errors->has('raid') ? ' has-error' : '' }}">
                {{ html()->label(__('view_admin.dungeon.edit.raid'), 'raid') }}
                {{ html()->checkbox('raid', $dungeon?->raid ?? 0, 1)->class('form-control left_checkbox') }}
                @include('common.forms.form-error', ['key' => 'raid'])
            </div>

            <div class="col {{ $errors->has('heatmap_enabled') ? ' has-error' : '' }}">
                {{ html()->label(__('view_admin.dungeon.edit.heatmap_enabled'), 'heatmap_enabled') }}
                {{ html()->checkbox('heatmap_enabled', $dungeon?->heatmap_enabled ?? 0, 1)->class('form-control left_checkbox') }}
                @include('common.forms.form-error', ['key' => 'heatmap_enabled'])
            </div>

            <div class="col {{ $errors->has('speedrun_enabled') ? ' has-error' : '' }}">
                {{ html()->label(__('view_admin.dungeon.edit.speedrun_enabled'), 'speedrun_enabled') }}
                {{ html()->checkbox('speedrun_enabled', $dungeon?->speedrun_enabled ?? 0, 1)->class('form-control left_checkbox') }}
                @include('common.forms.form-error', ['key' => 'speedrun_enabled'])
            </div>

            <div class="col {{ $errors->has('speedrun_difficulty_10_man_enabled') ? ' has-error' : '' }}">
                {{ html()->label(__('view_admin.dungeon.edit.speedrun_difficulty_10_man_enabled'), 'speedrun_difficulty_10_man_enabled') }}
                {{ html()->checkbox('speedrun_difficulty_10_man_enabled', $dungeon?->speedrun_difficulty_10_man_enabled ?? 0, 1)->class('form-control left_checkbox') }}
                @include('common.forms.form-error', ['key' => 'speedrun_difficulty_10_man_enabled'])
            </div>

            <div class="col {{ $errors->has('speedrun_difficulty_25_man_enabled') ? ' has-error' : '' }}">
                {{ html()->label(__('view_admin.dungeon.edit.speedrun_difficulty_25_man_enabled'), 'speedrun_difficulty_25_man_enabled') }}
                {{ html()->checkbox('speedrun_difficulty_25_man_enabled', $dungeon?->speedrun_difficulty_25_man_enabled ?? 0, 1)->class('form-control left_checkbox') }}
                @include('common.forms.form-error', ['key' => 'speedrun_difficulty_25_man_enabled'])
            </div>

            <div class="col {{ $errors->has('has_wallpaper') ? ' has-error' : '' }}">
                {{ html()->label(__('view_admin.dungeon.edit.has_wallpaper'), 'has_wallpaper') }}
                {{ html()->checkbox('has_wallpaper', $dungeon?->has_wallpaper ?? 0, 1)->class('form-control left_checkbox') }}
                @include('common.forms.form-error', ['key' => 'has_wallpaper'])
            </div>
        </div>

        <div class="form-group{{ $errors->has('key') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.dungeon.edit.key'), 'key') }}
            @isset($dungeon)
                {{ html()->text('key')->class('form-control')->disabled() }}
                {{ html()->hidden('key', $dungeon->key) }}
            @else
                {{ html()->select('key', $availableKeysSelect)->class('form-control selectpicker') }}
            @endisset
            @include('common.forms.form-error', ['key' => 'key'])
        </div>

        @isset($dungeon)
            <div class="form-group{{ $errors->has('id') ? ' has-error' : '' }}">
                {{ html()->label(__('view_admin.dungeon.edit.id'), 'id') }}
                {{ html()->number('id')->class('form-control')->attribute('disabled', 'disabled') }}
                @include('common.forms.form-error', ['key' => 'id'])
            </div>
        @endisset

        <div class="form-group{{ $errors->has('zone_id') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.dungeon.edit.zone_id'), 'zone_id') }}
            {{ html()->number('zone_id')->class('form-control') }}
            @include('common.forms.form-error', ['key' => 'zone_id'])
        </div>

        <div class="form-group{{ $errors->has('map_id') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.dungeon.edit.map_id'), 'map_id') }}
            {{ html()->number('map_id')->class('form-control') }}
            @include('common.forms.form-error', ['key' => 'map_id'])
        </div>

        <div class="form-group{{ $errors->has('instance_id') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.dungeon.edit.instance_id'), 'instance_id') }}
            {{ html()->number('instance_id')->class('form-control') }}
            @include('common.forms.form-error', ['key' => 'instance_id'])
        </div>

        <div class="form-group{{ $errors->has('challenge_mode_id') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.dungeon.edit.challenge_mode_id'), 'challenge_mode_id') }}
            {{ html()->number('challenge_mode_id')->class('form-control') }}
            @include('common.forms.form-error', ['key' => 'challenge_mode_id'])
        </div>

        <div class="form-group{{ $errors->has('mdt_id') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.dungeon.edit.mdt_id'), 'mdt_id') }}
            {{ html()->number('mdt_id')->class('form-control') }}
            @include('common.forms.form-error', ['key' => 'mdt_id'])
        </div>

        <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.dungeon.edit.dungeon_name'), 'name') }}
            {{ html()->text('name')->class('form-control') }}
            @include('common.forms.form-error', ['key' => 'name'])
        </div>

        <div class="form-group{{ $errors->has('slug') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.dungeon.edit.slug'), 'slug') }}
            {{ html()->text('slug')->class('form-control') }}
            @include('common.forms.form-error', ['key' => 'slug'])
        </div>

        {{ html()->input('submit')->value(__('view_admin.dungeon.edit.submit'))->class('btn btn-info') }}

        {{ html()->closeModelForm() }}
        @isset($dungeon)
    </div>

    @isset($dungeon)
        <div class="form-group">
            @include('admin.dungeon.floormanagement', ['dungeon' => $dungeon])
        </div>

        <div class="form-group">
            @include('admin.dungeon.mappingversions', ['dungeon' => $dungeon])
        </div>
    @endisset

    @endisset
@endsection
