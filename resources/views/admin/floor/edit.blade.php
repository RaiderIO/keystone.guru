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
        {{ html()->modelForm($floor, 'PATCH', route('admin.floor.update', ['dungeon' => $dungeon->slug, 'floor' => $floor->id]))->open() }}
    @else
        {{ html()->form('POST', route('admin.floor.savenew', $dungeon->slug))->open() }}
    @endisset

    <div class="row form-group">
        <div class="col {{ $errors->has('active') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.floor.edit.active'), 'active')->class('font-weight-bold') }}
            {{ html()->checkbox('active', $floor?->active, 1)->class('form-control left_checkbox') }}
            @include('common.forms.form-error', ['key' => 'active'])
        </div>

        <div class="col {{ $errors->has('default') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.floor.edit.default'), 'default')->class('font-weight-bold') }}
            <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                __('view_admin.floor.edit.default_title')
                 }}"></i>
            {{ html()->checkbox('default', $floor?->default ?? (int) ($dungeon->floors()->count() === 0), 1)->class('form-control left_checkbox') }}
            @include('common.forms.form-error', ['key' => 'default'])
        </div>

        <div class="col {{ $errors->has('facade') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.floor.edit.facade'), 'facade')->class('font-weight-bold') }}
            <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                __('view_admin.floor.edit.facade_title')
                 }}"></i>
            {{ html()->checkbox('facade', $floor?->facade, 1)->class('form-control left_checkbox') }}
            @include('common.forms.form-error', ['key' => 'facade'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('index') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.floor.edit.index'), 'index')->class('font-weight-bold') }}
        <span class="form-required">*</span>
        {{ html()->text('index', $floor?->index ?? $dungeon->floors()->count() + 1)->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'index'])
    </div>

    <div class="form-group{{ $errors->has('mdt_sub_level') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.floor.edit.mdt_sub_level'), 'mdt_sub_level')->class('font-weight-bold') }}
        <span class="form-required">*</span>
        {{ html()->text('mdt_sub_level', $floor?->mdt_sub_level)->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'mdt_sub_level'])
    </div>

    <div class="form-group{{ $errors->has('ui_map_id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.floor.edit.ui_map_id'), 'ui_map_id')->class('font-weight-bold') }}
        <span class="form-required">*</span>
        {{ html()->text('ui_map_id', $floor?->ui_map_id)->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'ui_map_id'])
    </div>

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.floor.edit.name'), 'name')->class('font-weight-bold') }}
        <span class="form-required">*</span>
        {{ html()->text('name')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    <div class="form-group{{ $errors->has('map_name') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.floor.edit.map_name'), 'map_name')->class('font-weight-bold') }}
        {{ html()->text('map_name')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'map_name'])
    </div>

    <div class="form-group{{ $errors->has('min_enemy_size') ? ' has-error' : '' }}">
        {{ html()->label(sprintf(__('view_admin.floor.edit.min_enemy_size'), config('keystoneguru.min_enemy_size_default')), 'min_enemy_size')->class('font-weight-bold') }}
        {{ html()->number('min_enemy_size')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'min_enemy_size'])
    </div>

    <div class="form-group{{ $errors->has('max_enemy_size') ? ' has-error' : '' }}">
        {{ html()->label(sprintf(__('view_admin.floor.edit.max_enemy_size'), config('keystoneguru.max_enemy_size_default')), 'max_enemy_size')->class('font-weight-bold') }}
        {{ html()->number('max_enemy_size')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'max_enemy_size'])
    </div>

    <div class="form-group{{ $errors->has('enemy_engagement_max_range') ? ' has-error' : '' }}">
        {{ html()->label(sprintf(__('view_admin.floor.edit.enemy_engagement_max_range'), config('keystoneguru.enemy_engagement_max_range_default')), 'enemy_engagement_max_range')->class('font-weight-bold') }}
        {{ html()->number('enemy_engagement_max_range', $floor?->enemy_engagement_max_range)->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'enemy_engagement_max_range'])
    </div>

    <div class="form-group{{ $errors->has('enemy_engagement_max_range_patrols') ? ' has-error' : '' }}">
        {{ html()->label(sprintf(__('view_admin.floor.edit.enemy_engagement_max_range_patrols'), config('keystoneguru.enemy_engagement_max_range_patrols_default')), 'enemy_engagement_max_range_patrols')->class('font-weight-bold') }}
        {{ html()->number('enemy_engagement_max_range_patrols', $floor?->enemy_engagement_max_range_patrols)->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'enemy_engagement_max_range_patrols'])
    </div>

    <div class="form-group{{ $errors->has('percentage_display_zoom') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.floor.edit.percentage_display_zoom'), 'percentage_display_zoom')->class('font-weight-bold') }}
        {{ html()->number('percentage_display_zoom')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'percentage_display_zoom'])
    </div>

    <div class="form-group{{ $errors->has('zoom_max') ? ' has-error' : '' }}">
        {{ html()->label(sprintf(__('view_admin.floor.edit.zoom_max'), config('keystoneguru.zoom_max_default')), 'zoom_max')->class('font-weight-bold') }}
        {{ html()->number('zoom_max')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'zoom_max'])
    </div>

    <div class="form-group">
        @include('admin.floor.connectedfloors', ['floor' => $floor])

        {{ html()->input('submit')->value(__('view_admin.floor.edit.submit'))->class('btn btn-info') }}

        {{ html()->closeModelForm() }}
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
