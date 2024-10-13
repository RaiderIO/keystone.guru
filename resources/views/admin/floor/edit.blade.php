<?php
/**
 * @var Dungeon                   $dungeon
 * @var Floor                     $floor
 * @var Collection<FloorCoupling> $floorCouplings
 */

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorCoupling;
use Illuminate\Support\Collection;

$floor ??= null;
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$dungeon, $floor ?? null],
    'showAds' => false,
    'title' => sprintf(
        isset($floor) ? __('view_admin.floor.edit.title_edit') : __('view_admin.floor.edit.title_new'),
        __($dungeon->name)
    )]
)
@section('header-title')
    {{ sprintf(isset($floor) ? __('view_admin.floor.edit.header_edit') : __('view_admin.floor.edit.header_new'), __($dungeon->name)) }}
@endsection

@section('content')
    @isset($floor)
        {{ Form::model($floor, ['route' => ['admin.floor.update', 'dungeon' => $dungeon->slug, 'floor' => $floor->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => ['admin.floor.savenew', 'dungeon' => $dungeon->slug]]) }}
    @endisset

    <div class="row form-group">
        <div class="col {{ $errors->has('active') ? ' has-error' : '' }}">
            {!! Form::label('active', __('view_admin.floor.edit.active'), ['class' => 'font-weight-bold']) !!}
            {!! Form::checkbox('active', 1, $floor?->active, ['class' => 'form-control left_checkbox']) !!}
            @include('common.forms.form-error', ['key' => 'active'])
        </div>

        <div class="col {{ $errors->has('default') ? ' has-error' : '' }}">
            {!! Form::label('default', __('view_admin.floor.edit.default'), ['class' => 'font-weight-bold']) !!}
            <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                __('view_admin.floor.edit.default_title')
                 }}"></i>
            {!! Form::checkbox('default', 1, $floor?->default ?? (int)($dungeon->floors()->count() === 0), ['class' => 'form-control left_checkbox']) !!}
            @include('common.forms.form-error', ['key' => 'default'])
        </div>

        <div class="col {{ $errors->has('facade') ? ' has-error' : '' }}">
            {!! Form::label('facade', __('view_admin.floor.edit.facade'), ['class' => 'font-weight-bold']) !!}
            <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                __('view_admin.floor.edit.facade_title')
                 }}"></i>
            {!! Form::checkbox('facade', 1, $floor?->facade, ['class' => 'form-control left_checkbox']) !!}
            @include('common.forms.form-error', ['key' => 'facade'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('index') ? ' has-error' : '' }}">
        {!! Form::label('index', __('view_admin.floor.edit.index'), ['class' => 'font-weight-bold']) !!}
        <span class="form-required">*</span>
        {!! Form::text('index', $floor?->index ?? $dungeon->floors()->count() + 1, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'index'])
    </div>

    <div class="form-group{{ $errors->has('mdt_sub_level') ? ' has-error' : '' }}">
        {!! Form::label('mdt_sub_level', __('view_admin.floor.edit.mdt_sub_level'), ['class' => 'font-weight-bold']) !!}
        <span class="form-required">*</span>
        {!! Form::text('mdt_sub_level', $floor?->mdt_sub_level, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'mdt_sub_level'])
    </div>

    <div class="form-group{{ $errors->has('ui_map_id') ? ' has-error' : '' }}">
        {!! Form::label('ui_map_id', __('view_admin.floor.edit.ui_map_id'), ['class' => 'font-weight-bold']) !!}
        <span class="form-required">*</span>
        {!! Form::text('ui_map_id', $floor?->ui_map_id, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'ui_map_id'])
    </div>

    <div class="form-group{{ $errors->has('map_name') ? ' has-error' : '' }}">
        {!! Form::label('map_name', __('view_admin.floor.edit.map_name'), ['class' => 'font-weight-bold']) !!}
        <span class="form-required">*</span>
        {!! Form::text('map_name', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'map_name'])
    </div>

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('name', __('view_admin.floor.edit.name'), ['class' => 'font-weight-bold']) !!}
        <span class="form-required">*</span>
        {!! Form::text('name', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    <div class="form-group{{ $errors->has('min_enemy_size') ? ' has-error' : '' }}">
        {!! Form::label('min_enemy_size', sprintf(__('view_admin.floor.edit.min_enemy_size'), config('keystoneguru.min_enemy_size_default')), ['class' => 'font-weight-bold']) !!}
        {!! Form::number('min_enemy_size', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'min_enemy_size'])
    </div>

    <div class="form-group{{ $errors->has('max_enemy_size') ? ' has-error' : '' }}">
        {!! Form::label('max_enemy_size', sprintf(__('view_admin.floor.edit.max_enemy_size'), config('keystoneguru.max_enemy_size_default')), ['class' => 'font-weight-bold']) !!}
        {!! Form::number('max_enemy_size', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'max_enemy_size'])
    </div>

    <div class="form-group{{ $errors->has('enemy_engagement_max_range') ? ' has-error' : '' }}">
        {!! Form::label('enemy_engagement_max_range', sprintf(__('view_admin.floor.edit.enemy_engagement_max_range'), config('keystoneguru.enemy_engagement_max_range_default')), ['class' => 'font-weight-bold']) !!}
        {!! Form::number('enemy_engagement_max_range', $floor?->enemy_engagement_max_range, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'enemy_engagement_max_range'])
    </div>

    <div class="form-group{{ $errors->has('enemy_engagement_max_range_patrols') ? ' has-error' : '' }}">
        {!! Form::label('enemy_engagement_max_range_patrols', sprintf(__('view_admin.floor.edit.enemy_engagement_max_range_patrols'), config('keystoneguru.enemy_engagement_max_range_patrols_default')), ['class' => 'font-weight-bold']) !!}
        {!! Form::number('enemy_engagement_max_range_patrols', $floor?->enemy_engagement_max_range_patrols, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'enemy_engagement_max_range_patrols'])
    </div>

    <div class="form-group{{ $errors->has('percentage_display_zoom') ? ' has-error' : '' }}">
        {!! Form::label('percentage_display_zoom', __('view_admin.floor.edit.percentage_display_zoom'), ['class' => 'font-weight-bold']) !!}
        {!! Form::number('percentage_display_zoom', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'percentage_display_zoom'])
    </div>

    <div class="form-group{{ $errors->has('zoom_max') ? ' has-error' : '' }}">
        {!! Form::label('zoom_max', sprintf(__('view_admin.floor.edit.zoom_max'), config('keystoneguru.zoom_max_default')), ['class' => 'font-weight-bold']) !!}
        {!! Form::number('zoom_max', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'zoom_max'])
    </div>

    <div class="form-group">
        @include('admin.floor.connectedfloors', ['floor' => $floor])

        {!! Form::submit(__('view_admin.floor.edit.submit'), ['class' => 'btn btn-info']) !!}

        {!! Form::close() !!}
    </div>

    @isset($floor)
        <div class="form-group">
            @include('admin.floor.speedrunrequirednpcs', ['difficulty' => Dungeon::DIFFICULTY_10_MAN, 'floor' => $floor])
        </div>
        <div class="form-group">
            @include('admin.floor.speedrunrequirednpcs', ['difficulty' => Dungeon::DIFFICULTY_25_MAN, 'floor' => $floor])
        </div>
    @endisset
@endsection
