<?php

use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcHealth;
use Illuminate\Support\Collection;

/**
 * @var Npc                     $npc
 * @var NpcHealth               $npcHealth
 * @var Collection<GameVersion> $allGameVersions
 */

$npcHealth            = $npcHealth ?? null;
$existingGameVersions = $npc->npcHealths->keyBy('game_version_id');
$gameVersionsSelect   = $allGameVersions
    ->mapWithKeys(static fn(GameVersion $gameVersion) => [$gameVersion->id => __($gameVersion->name)])
    // When editing, we want the game version of the npc health to be available still
    ->when($npcHealth !== null, static function (Collection $collection) use ($npcHealth, $existingGameVersions) {
        // Remove it from the existing game versions so it can be selected again
        unset($existingGameVersions[$npcHealth->game_version_id]);

        $collection->put($npcHealth->game_version_id, sprintf('%s (%s)',
                __($npcHealth->gameVersion->name),
                __('view_admin.npchealth.edit.current')
            )
        );
    })
    ->except($existingGameVersions->keys());
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$npc, $npcHealth],
    'showAds' => false,
    'title' => __('view_admin.npchealth.edit.title', ['name' => $npc->name]),
])
@section('header-title')
    {{ __('view_admin.npchealth.edit.header', ['name' => $npc->name]) }}
@endsection

@include('common.general.inline', ['path' => 'admin/npchealth/edit', 'options' => [
    'healthSelector' => '#health',
    'scaledHealthSelector' => '#scaled_health',
    'scaledHealthToBaseHealthApplyBtnSelector' => '#scaled_health_to_health_apply_btn',
    'scaledHealthPercentageSelector' => '#scaled_health_percentage',
    'scaledHealthLevelSelector' => '#scaled_health_level',
    'scaledHealthTypeSelector' => '#scaled_health_type',
    'healthPercentageSelector' => '#percentage',
]])

@section('content')
    @isset($npcHealth)
        {{ Form::model($npc, ['route' => ['admin.npc.npchealth.update', $npc, $npcHealth], 'autocomplete' => 'off', 'method' => 'patch']) }}
    @else
        {{ Form::model($npc, ['route' => ['admin.npc.npchealth.savenew', $npc], 'autocomplete' => 'off', 'method' => 'post']) }}
    @endisset

    <div class="form-group{{ $errors->has('game_version_id') ? ' has-error' : '' }}">
        {!! Form::label('game_version_id', __('view_admin.npchealth.edit.game_version'), [], false) !!}
        <span class="form-required">*</span>
        {!! Form::select('game_version_id', $gameVersionsSelect, $npcHealth?->game_version_id, ['class' => 'form-control selectpicker']) !!}
        @include('common.forms.form-error', ['key' => 'game_version_id'])
    </div>


    <div class="form-group{{ $errors->has('health') ? ' has-error' : '' }}">
        {!! Form::label('health', __('view_admin.npchealth.edit.health'), [], false) !!}
        <span class="form-required">*</span>
        <div class="row">
            <div class="col-3">
                {!! Form::number('health', $npcHealth?->health, ['class' => 'form-control']) !!}
                @include('common.forms.form-error', ['key' => 'health'])
            </div>
            <div class="col-9">
                <div class="row">
                    <div class="col-auto">
                        <div id="scaled_health_to_health_apply_btn" class="btn btn-info">
                            {{ __('view_admin.npchealth.edit.scaled_health_to_health_apply') }}
                        </div>
                    </div>
                    <div class="col">
                        {!! Form::text('scaled_health', null, [
                            'id' => 'scaled_health',
                            'class' => 'form-control',
                            'placeholder' => __('view_admin.npchealth.edit.scaled_health_placeholder'),
                        ]) !!}
                    </div>
                    <div class="col">
                        {!! Form::text('scaled_health_percentage', null, [
                            'id' => 'scaled_health_percentage',
                            'class' => 'form-control',
                            'placeholder' => __('view_admin.npchealth.edit.scaled_health_percentage_placeholder'),
                            ]) !!}
                    </div>
                    <div class="col">
                        {!! Form::text('scaled_health_level', null, ['id' => 'scaled_health_level', 'class' => 'form-control', 'style' => 'display: none;']) !!}
                    </div>
                    <div class="col">
                        {!!
                            Form::select('scaled_health_type',
                            [
                                'none' => __('view_admin.npchealth.edit.scaled_type_none'),
                                'fortified' => __('view_admin.npchealth.edit.scaled_type_fortified', ['affix' => __('affixes.fortified.name')]),
                                'tyrannical' => __('view_admin.npchealth.edit.scaled_type_tyrannical', ['affix' => __('affixes.tyrannical.name')]),
                            ],
                            null,
                            ['id' => 'scaled_health_type', 'class' => 'form-control selectpicker'])
                        !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group{{ $errors->has('percentage') ? ' has-error' : '' }}">
        {!! Form::label('percentage', __('view_admin.npchealth.edit.percentage')) !!}
        {!! Form::number('percentage', $npcHealth?->percentage ?? 100, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'percentage'])
    </div>

    <div class="form-group">
        {!! Form::submit(__('view_admin.npchealth.edit.submit'), ['class' => 'btn btn-info', 'name' => 'submit', 'value' => 'submit']) !!}
    </div>

    {!! Form::close() !!}
@endsection
