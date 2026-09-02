<?php
/** @var array<int|string, string> $seasons */
/** @var array<string, array<int, string>> $periods */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.combatlog.regenerate.title')])

@section('header-title', __('view_admin.tools.combatlog.regenerate.header'))

@section('content')
    {{ html()->form('POST', route('admin.tools.combatlog.regenerate.submit'))->open() }}
    <div class="mb-3">
        @include('common.dungeon.select', [
            'activeOnly'           => false,
            'allowSeasonSelection' => true,
            'showSeasons'          => true,
        ])
    </div>
    <div class="mb-3">
        {{ html()->label(__('view_admin.tools.combatlog.regenerate.season'), 'season_id') }}
        {{ html()->select('season_id', $seasons, request()->input('season_id'))
            ->attributes([
                'id'               => 'season_id',
                'class'            => 'form-control selectpicker',
                'data-live-search' => 'true',
            ]) }}
        <div class="form-text">{{ __('view_admin.tools.combatlog.regenerate.season_description') }}</div>
    </div>
    <div class="mb-3">
        {{ html()->label(__('view_admin.tools.combatlog.regenerate.periods'), 'periods') }}
        {{ html()->multiselect('periods[]', $periods, (array)request()->input('periods', []))
            ->attributes([
                'id'               => 'periods',
                'class'            => 'form-control selectpicker',
                'data-live-search' => 'true',
                'data-actions-box' => 'true',
            ]) }}
        <div class="form-text">{{ __('view_admin.tools.combatlog.regenerate.periods_description') }}</div>
    </div>
    <div class="mb-3">
        <div class="form-check">
            {{ html()->checkbox('delete_enemy_failures', false, 1)->class('form-check-input') }}
            {{ html()->label(__('view_admin.tools.combatlog.regenerate.delete_enemy_failures'), 'delete_enemy_failures')->class('form-check-label') }}
        </div>
        <div class="form-text">{{ __('view_admin.tools.combatlog.regenerate.delete_enemy_failures_description') }}</div>
    </div>
    <div class="mb-3">
        {{ html()->input('submit')->value(__('view_admin.tools.combatlog.regenerate.submit'))->class('btn btn-primary col-md-auto') }}
    </div>
    {{ html()->form()->close() }}
@endsection
