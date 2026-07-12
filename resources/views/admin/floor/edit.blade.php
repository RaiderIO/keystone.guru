<?php
/**
 * @var Dungeon                        $dungeon
 * @var Floor                          $floor
 * @var Collection<int, FloorCoupling> $floorCouplings
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
        __($floor->name)
    )]
)
@section('header-title')
    {{ sprintf(isset($floor) ? __('view_admin.floor.edit.header_edit') : __('view_admin.floor.edit.header_new'), __($floor->name)) }}
@endsection

@section('content')
    @isset($floor)
        {{ html()->modelForm($floor, 'PATCH', route('admin.floor.update', ['dungeon' => $dungeon->slug, 'floor' => $floor->id]))->open() }}
    @else
        {{ html()->form('POST', route('admin.floor.savenew', $dungeon->slug))->open() }}
    @endisset

    <div class="row mb-3">
        <div class="col {{ $errors->has('active') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.floor.edit.active'), 'active')->class('fw-bold') }}
            {{ html()->checkbox('active', $floor?->active, 1)->class('form-check-input') }}
            @include('common.forms.form-error', ['key' => 'active'])
        </div>

        <div class="col {{ $errors->has('default') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.floor.edit.default'), 'default')->class('fw-bold') }}
            <i class="fas fa-info-circle" data-bs-toggle="tooltip" title="{{
                __('view_admin.floor.edit.default_title')
                 }}"></i>
            {{ html()->checkbox('default', $floor?->default ?? (int) ($dungeon->floors()->count() === 0), 1)->class('form-check-input') }}
            @include('common.forms.form-error', ['key' => 'default'])
        </div>

        <div class="col {{ $errors->has('facade') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.floor.edit.facade'), 'facade')->class('fw-bold') }}
            <i class="fas fa-info-circle" data-bs-toggle="tooltip" title="{{
                __('view_admin.floor.edit.facade_title')
                 }}"></i>
            {{ html()->checkbox('facade', $floor?->facade, 1)->class('form-check-input') }}
            @include('common.forms.form-error', ['key' => 'facade'])
        </div>
    </div>

    <div class="mb-3{{ $errors->has('index') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.floor.edit.index'), 'index')->class('fw-bold') }}
        <span class="form-required">*</span>
        {{ html()->text('index', $floor?->index ?? $dungeon->floors()->count() + 1)->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'index'])
    </div>

    <div class="mb-3{{ $errors->has('mdt_sub_level') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.floor.edit.mdt_sub_level'), 'mdt_sub_level')->class('fw-bold') }}
        <span class="form-required">*</span>
        {{ html()->text('mdt_sub_level', $floor?->mdt_sub_level)->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'mdt_sub_level'])
    </div>

    <div class="mb-3{{ $errors->has('ui_map_id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.floor.edit.ui_map_id'), 'ui_map_id')->class('fw-bold') }}
        <span class="form-required">*</span>
        {{ html()->text('ui_map_id', $floor?->ui_map_id)->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'ui_map_id'])
    </div>

    <div class="mb-3{{ $errors->has('name') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.floor.edit.name'), 'name')->class('fw-bold') }}
        <span class="form-required">*</span>
        {{ html()->text('name')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    <div class="mb-3{{ $errors->has('map_name') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.floor.edit.map_name'), 'map_name')->class('fw-bold') }}
        {{ html()->text('map_name')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'map_name'])
    </div>

    <div class="mb-3{{ $errors->has('min_enemy_size') ? ' has-error' : '' }}">
        {{ html()->label(sprintf(__('view_admin.floor.edit.min_enemy_size'), config('keystoneguru.min_enemy_size_default')), 'min_enemy_size')->class('fw-bold') }}
        {{ html()->number('min_enemy_size')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'min_enemy_size'])
    </div>

    <div class="mb-3{{ $errors->has('max_enemy_size') ? ' has-error' : '' }}">
        {{ html()->label(sprintf(__('view_admin.floor.edit.max_enemy_size'), config('keystoneguru.max_enemy_size_default')), 'max_enemy_size')->class('fw-bold') }}
        {{ html()->number('max_enemy_size')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'max_enemy_size'])
    </div>

    <div class="mb-3{{ $errors->has('enemy_engagement_max_range') ? ' has-error' : '' }}">
        {{ html()->label(sprintf(__('view_admin.floor.edit.enemy_engagement_max_range'), config('keystoneguru.enemy_engagement_max_range_default')), 'enemy_engagement_max_range')->class('fw-bold') }}
        {{ html()->number('enemy_engagement_max_range', $floor?->enemy_engagement_max_range)->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'enemy_engagement_max_range'])
    </div>

    <div class="mb-3{{ $errors->has('enemy_engagement_max_range_patrols') ? ' has-error' : '' }}">
        {{ html()->label(sprintf(__('view_admin.floor.edit.enemy_engagement_max_range_patrols'), config('keystoneguru.enemy_engagement_max_range_patrols_default')), 'enemy_engagement_max_range_patrols')->class('fw-bold') }}
        {{ html()->number('enemy_engagement_max_range_patrols', $floor?->enemy_engagement_max_range_patrols)->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'enemy_engagement_max_range_patrols'])
    </div>

    <div class="mb-3{{ $errors->has('percentage_display_zoom') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.floor.edit.percentage_display_zoom'), 'percentage_display_zoom')->class('fw-bold') }}
        {{ html()->number('percentage_display_zoom')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'percentage_display_zoom'])
    </div>

    <div class="mb-3{{ $errors->has('zoom_max') ? ' has-error' : '' }}">
        {{ html()->label(sprintf(__('view_admin.floor.edit.zoom_max'), config('keystoneguru.zoom_max_default')), 'zoom_max')->class('fw-bold') }}
        {{ html()->number('zoom_max')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'zoom_max'])
    </div>

    <div class="mb-3">
        @include('admin.floor.connectedfloors', ['floor' => $floor])

        {{ html()->input('submit')->value(__('view_admin.floor.edit.submit'))->class('btn btn-info') }}

        {{ html()->closeModelForm() }}
    </div>

    @isset($floor)
        <div class="mb-3">
            @include('admin.floor.speedrunrequirednpcs', ['dungeon' => $dungeon, 'floor' => $floor])
        </div>
    @endisset
@endsection
