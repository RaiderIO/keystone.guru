<?php
/**
 * @var $dungeon             \App\Models\Dungeon
 * @var $floor               \App\Models\Floor\Floor
 * @var $availableKeysSelect \Illuminate\Support\Collection
 */

$gameVersionsSelect = \App\Models\GameVersion\GameVersion::all()
    ->mapWithKeys(static fn(\App\Models\GameVersion\GameVersion $gameVersion) => [$gameVersion->id => __($gameVersion->name)]);
?>

@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$dungeon ?? null],
    'showAds' => false,
    'title' => isset($dungeon) ? __('view_admin.dungeon.edit.title_edit') : __('view_admin.dungeon.edit.title_new')
    ])

@section('header-title')
    {{ isset($dungeon) ? __('view_admin.dungeon.edit.header_edit') : __('view_admin.dungeon.edit.header_new') }}
@endsection

@section('content')
    <div class="mb-4">
        @isset($dungeon)
            {{ Form::model($dungeon, ['route' => ['admin.dungeon.update', $dungeon->slug], 'method' => 'patch']) }}
        @else
            {{ Form::open(['route' => 'admin.dungeon.savenew']) }}
        @endisset

        <div class="row form-group">
            <div class="col {{ $errors->has('active') ? ' has-error' : '' }}">
                {!! Form::label('active', __('view_admin.dungeon.edit.active')) !!}
                {!! Form::checkbox('active', 1, $dungeon?->active ?? 1, ['class' => 'form-control left_checkbox']) !!}
                @include('common.forms.form-error', ['key' => 'active'])
            </div>

            <div class="col {{ $errors->has('speedrun_enabled') ? ' has-error' : '' }}">
                {!! Form::label('speedrun_enabled', __('view_admin.dungeon.edit.speedrun_enabled')) !!}
                {!! Form::checkbox('speedrun_enabled', 1, $dungeon?->speedrun_enabled ?? 0, ['class' => 'form-control left_checkbox']) !!}
                @include('common.forms.form-error', ['key' => 'speedrun_enabled'])
            </div>

            <div class="col {{ $errors->has('speedrun_difficulty_10_man_enabled') ? ' has-error' : '' }}">
                {!! Form::label('speedrun_difficulty_10_man_enabled', __('view_admin.dungeon.edit.speedrun_difficulty_10_man_enabled')) !!}
                {!! Form::checkbox('speedrun_difficulty_10_man_enabled', 1, $dungeon?->speedrun_difficulty_10_man_enabled ?? 0, ['class' => 'form-control left_checkbox']) !!}
                @include('common.forms.form-error', ['key' => 'speedrun_difficulty_10_man_enabled'])
            </div>

            <div class="col {{ $errors->has('speedrun_difficulty_25_man_enabled') ? ' has-error' : '' }}">
                {!! Form::label('speedrun_difficulty_25_man_enabled', __('view_admin.dungeon.edit.speedrun_difficulty_25_man_enabled')) !!}
                {!! Form::checkbox('speedrun_difficulty_25_man_enabled', 1, $dungeon?->speedrun_difficulty_25_man_enabled ?? 0, ['class' => 'form-control left_checkbox']) !!}
                @include('common.forms.form-error', ['key' => 'speedrun_difficulty_25_man_enabled'])
            </div>
        </div>

        <div class="form-group{{ $errors->has('key') ? ' has-error' : '' }}">
            {!! Form::label('key', __('view_admin.dungeon.edit.key')) !!}
            @isset($dungeon)
                {!! Form::text('key', null, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
                {!! Form::hidden('key', $dungeon->key) !!}
            @else
                {!! Form::select('key', $availableKeysSelect, null, ['class' => 'form-control selectpicker']) !!}
            @endisset
            @include('common.forms.form-error', ['key' => 'key'])
        </div>

        @isset($dungeon)
            <div class="form-group{{ $errors->has('id') ? ' has-error' : '' }}">
                {!! Form::label('id', __('view_admin.dungeon.edit.id')) !!}
                {!! Form::number('id', null, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
                @include('common.forms.form-error', ['key' => 'id'])
            </div>
        @endisset

        <div class="form-group{{ $errors->has('game_version_id') ? ' has-error' : '' }}">
            {!! Form::label('game_version_id', __('view_admin.dungeon.edit.game_version_id'), [], false) !!}
            <span class="form-required">*</span>
            {!! Form::select('game_version_id', $gameVersionsSelect, null, ['class' => 'form-control selectpicker']) !!}
            @include('common.forms.form-error', ['key' => 'game_version_id'])
        </div>

        <div class="form-group{{ $errors->has('zone_id') ? ' has-error' : '' }}">
            {!! Form::label('zone_id', __('view_admin.dungeon.edit.zone_id')) !!}
            {!! Form::number('zone_id', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'zone_id'])
        </div>

        <div class="form-group{{ $errors->has('map_id') ? ' has-error' : '' }}">
            {!! Form::label('map_id', __('view_admin.dungeon.edit.map_id')) !!}
            {!! Form::number('map_id', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'map_id'])
        </div>

        <div class="form-group{{ $errors->has('instance_id') ? ' has-error' : '' }}">
            {!! Form::label('instance_id', __('view_admin.dungeon.edit.instance_id')) !!}
            {!! Form::number('instance_id', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'instance_id'])
        </div>

        <div class="form-group{{ $errors->has('challenge_mode_id') ? ' has-error' : '' }}">
            {!! Form::label('challenge_mode_id', __('view_admin.dungeon.edit.challenge_mode_id')) !!}
            {!! Form::number('challenge_mode_id', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'challenge_mode_id'])
        </div>

        <div class="form-group{{ $errors->has('mdt_id') ? ' has-error' : '' }}">
            {!! Form::label('mdt_id', __('view_admin.dungeon.edit.mdt_id')) !!}
            {!! Form::number('mdt_id', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'mdt_id'])
        </div>

        <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
            {!! Form::label('name', __('view_admin.dungeon.edit.dungeon_name')) !!}
            {!! Form::text('name', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'name'])
        </div>

        <div class="form-group{{ $errors->has('slug') ? ' has-error' : '' }}">
            {!! Form::label('slug', __('view_admin.dungeon.edit.slug')) !!}
            {!! Form::text('slug', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'slug'])
        </div>

        {!! Form::submit(__('view_admin.dungeon.edit.submit'), ['class' => 'btn btn-info']) !!}

        {!! Form::close() !!}
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
