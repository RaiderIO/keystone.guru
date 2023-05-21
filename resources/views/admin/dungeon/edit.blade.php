@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$dungeon ?? null],
    'showAds' => false,
    'title' => $dungeon ? __('views/admin.dungeon.edit.title_edit') : __('views/admin.dungeon.edit.title_new')
    ])

@section('header-title')
    {{ $dungeon ? __('views/admin.dungeon.edit.header_edit') : __('views/admin.dungeon.edit.header_new') }}
@endsection
<?php
/**
 * @var $dungeon \App\Models\Dungeon
 * @var $floor \App\Models\Floor
 */
?>

@section('content')
    <div class="mb-4">
        @isset($dungeon)
            {{ Form::model($dungeon, ['route' => ['admin.dungeon.update', $dungeon->slug], 'method' => 'patch']) }}
        @else
            {{ Form::open(['route' => 'admin.dungeon.savenew']) }}
        @endisset

        <div class="row form-group">
            <div class="col {{ $errors->has('active') ? ' has-error' : '' }}">
                {!! Form::label('active', __('views/admin.dungeon.edit.active')) !!}
                {!! Form::checkbox('active', 1, isset($dungeon) ? $dungeon->active : 1, ['class' => 'form-control left_checkbox']) !!}
                @include('common.forms.form-error', ['key' => 'active'])
            </div>

            <div class="col {{ $errors->has('speedrun_enabled') ? ' has-error' : '' }}">
                {!! Form::label('speedrun_enabled', __('views/admin.dungeon.edit.speedrun_enabled')) !!}
                {!! Form::checkbox('speedrun_enabled', 1, isset($dungeon) ? $dungeon->speedrun_enabled : 1, ['class' => 'form-control left_checkbox']) !!}
                @include('common.forms.form-error', ['key' => 'speedrun_enabled'])
            </div>
        </div>

        <div class="form-group{{ $errors->has('zone_id') ? ' has-error' : '' }}">
            {!! Form::label('id', __('views/admin.dungeon.edit.id')) !!}
            {!! Form::number('id', null, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
            @include('common.forms.form-error', ['key' => 'id'])
        </div>

        <div class="form-group{{ $errors->has('zone_id') ? ' has-error' : '' }}">
            {!! Form::label('zone_id', __('views/admin.dungeon.edit.zone_id')) !!}
            {!! Form::number('zone_id', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'zone_id'])
        </div>

        <div class="form-group{{ $errors->has('map_id') ? ' has-error' : '' }}">
            {!! Form::label('map_id', __('views/admin.dungeon.edit.map_id')) !!}
            {!! Form::number('map_id', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'map_id'])
        </div>

        <div class="form-group{{ $errors->has('mdt_id') ? ' has-error' : '' }}">
            {!! Form::label('mdt_id', __('views/admin.dungeon.edit.mdt_id')) !!}
            {!! Form::number('mdt_id', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'mdt_id'])
        </div>

        <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
            {!! Form::label('name', __('views/admin.dungeon.edit.dungeon_name')) !!}
            {!! Form::text('name', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'name'])
        </div>

        <div class="form-group{{ $errors->has('key') ? ' has-error' : '' }}">
            {!! Form::label('key', __('views/admin.dungeon.edit.key')) !!}
            {!! Form::text('key', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'key'])
        </div>

        <div class="form-group{{ $errors->has('slug') ? ' has-error' : '' }}">
            {!! Form::label('slug', __('views/admin.dungeon.edit.slug')) !!}
            {!! Form::text('slug', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'slug'])
        </div>

        {!! Form::submit(__('views/admin.dungeon.edit.submit'), ['class' => 'btn btn-info']) !!}

        {!! Form::close() !!}
        @isset($dungeon)
    </div>

    <div class="form-group">
        @include('admin.dungeon.floormanagement', ['dungeon' => $dungeon])
    </div>

    <div class="form-group">
        @include('admin.dungeon.mappingversions', ['dungeon' => $dungeon])
    </div>

    @endisset
@endsection
