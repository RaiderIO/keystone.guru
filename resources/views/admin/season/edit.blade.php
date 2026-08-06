<?php

use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * @var Season                  $season
 * @var Collection<int, string> $expansions
 * @var Collection<int, string> $seasonalAffixSelect
 * @var Collection<int, string> $availableSeasonIds Only present when creating a new season.
 * @var array<int, int>         $selectedDungeonIds
 */
?>

@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$season ?? null],
    'showAds' => false,
    'title' => isset($season) ? __('view_admin.season.edit.title_edit') : __('view_admin.season.edit.title_new'),
    ])

@section('header-title')
    {{ isset($season) ? __('view_admin.season.edit.header_edit') : __('view_admin.season.edit.header_new') }}
@endsection

@section('content')
    @if(!isset($season) && $availableSeasonIds->isEmpty())
        <div class="alert alert-warning">
            {{ __('view_admin.season.edit.no_available_ids') }}
        </div>
    @else
    <div class="mb-4">
        @isset($season)
            {{ html()->modelForm($season, 'PATCH', route('admin.season.update', $season))->open() }}
        @else
            {{ html()->form('POST', route('admin.season.savenew'))->open() }}
        @endisset

        @isset($season)
            <div class="mb-3">
                {{ html()->label(__('view_admin.season.edit.id'), 'id') }}
                {{ html()->number('id')->class('form-control')->attribute('disabled', 'disabled') }}
            </div>
        @else
            <div class="mb-3{{ $errors->has('id') ? ' has-error' : '' }}">
                {{ html()->label(__('view_admin.season.edit.id'), 'id') }}
                <span class="form-required">*</span>
                {{ html()->select('id', $availableSeasonIds)->class('form-control selectpicker') }}
                @include('common.forms.form-error', ['key' => 'id'])
            </div>
        @endisset

        <div class="mb-3{{ $errors->has('expansion_id') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.season.edit.expansion_id'), 'expansion_id') }}
            {{ html()->select('expansion_id', $expansions)->class('form-control selectpicker') }}
            @include('common.forms.form-error', ['key' => 'expansion_id'])
        </div>

        <div class="mb-3{{ $errors->has('active') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.season.edit.active'), 'active') }}
            {{ html()->checkbox('active', ($season ?? null)?->active ?? 1, 1)->class('form-check-input') }}
            <small class="text-muted d-block">{{ __('view_admin.season.edit.active_help') }}</small>
            @include('common.forms.form-error', ['key' => 'active'])
        </div>

        <div class="mb-3{{ $errors->has('index') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.season.edit.index'), 'index') }}
            {{ html()->number('index')->class('form-control') }}
            @include('common.forms.form-error', ['key' => 'index'])
        </div>

        <div class="mb-3{{ $errors->has('start') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.season.edit.start'), 'start') }}
            {{ html()->input('datetime-local', 'start', ($season ?? null)?->start?->format('Y-m-d\TH:i'))->class('form-control') }}
            @include('common.forms.form-error', ['key' => 'start'])
        </div>

        <div class="mb-3{{ $errors->has('seasonal_affix_id') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.season.edit.seasonal_affix_id'), 'seasonal_affix_id') }}
            {{ html()->select('seasonal_affix_id', $seasonalAffixSelect, old('seasonal_affix_id', ($season ?? null)?->seasonal_affix_id ?? -1))->class('form-control selectpicker') }}
            @include('common.forms.form-error', ['key' => 'seasonal_affix_id'])
        </div>

        <div class="mb-3{{ $errors->has('presets') ? ' has-error' : '' }}">
            {{ html()->label(__('view_admin.season.edit.presets'), 'presets') }}
            {{ html()->number('presets', ($season ?? null)?->presets ?? 0)->class('form-control') }}
            @include('common.forms.form-error', ['key' => 'presets'])
        </div>

        <div class="row mb-3">
            <div class="col {{ $errors->has('affix_group_count') ? ' has-error' : '' }}">
                {{ html()->label(__('view_admin.season.edit.affix_group_count'), 'affix_group_count') }}
                {{ html()->number('affix_group_count')->class('form-control') }}
                @isset($season)
                    <small class="text-muted">{{ __('view_admin.season.edit.affix_group_count_actual', ['count' => $season->affixGroups()->count()]) }}</small>
                @endisset
                @include('common.forms.form-error', ['key' => 'affix_group_count'])
            </div>

            <div class="col {{ $errors->has('start_affix_group_index') ? ' has-error' : '' }}">
                {{ html()->label(__('view_admin.season.edit.start_affix_group_index'), 'start_affix_group_index') }}
                {{ html()->number('start_affix_group_index')->class('form-control') }}
                @include('common.forms.form-error', ['key' => 'start_affix_group_index'])
            </div>
        </div>

        <div class="row mb-3">
            <div class="col {{ $errors->has('key_level_min') ? ' has-error' : '' }}">
                {{ html()->label(__('view_admin.season.edit.key_level_min'), 'key_level_min') }}
                {{ html()->number('key_level_min')->class('form-control') }}
                @include('common.forms.form-error', ['key' => 'key_level_min'])
            </div>

            <div class="col {{ $errors->has('key_level_max') ? ' has-error' : '' }}">
                {{ html()->label(__('view_admin.season.edit.key_level_max'), 'key_level_max') }}
                {{ html()->number('key_level_max')->class('form-control') }}
                @include('common.forms.form-error', ['key' => 'key_level_max'])
            </div>
        </div>

        <div class="row mb-3">
            <div class="col {{ $errors->has('item_level_min') ? ' has-error' : '' }}">
                {{ html()->label(__('view_admin.season.edit.item_level_min'), 'item_level_min') }}
                {{ html()->number('item_level_min')->class('form-control') }}
                @include('common.forms.form-error', ['key' => 'item_level_min'])
            </div>

            <div class="col {{ $errors->has('item_level_max') ? ' has-error' : '' }}">
                {{ html()->label(__('view_admin.season.edit.item_level_max'), 'item_level_max') }}
                {{ html()->number('item_level_max')->class('form-control') }}
                @include('common.forms.form-error', ['key' => 'item_level_max'])
            </div>
        </div>

        @include('common.dungeon.select', [
            'name' => 'dungeon_ids[]',
            'selected' => $selectedDungeonIds,
            'multiple' => true,
            'showAll' => false,
            'activeOnly' => false,
            'ignoreGameVersion' => true,
            'label' => __('view_admin.season.edit.dungeon_ids'),
        ])
        @include('common.forms.form-error', ['key' => 'dungeon_ids'])

        {{ html()->input('submit')->value(__('view_admin.season.edit.submit'))->class('btn btn-info') }}

        {{ html()->closeModelForm() }}
    </div>
    @endif

    @isset($season)
        <div class="mb-3">
            @include('admin.season.affixgroups', ['season' => $season])
        </div>
    @endisset
@endsection
