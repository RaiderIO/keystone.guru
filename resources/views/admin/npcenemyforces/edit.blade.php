<?php

use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcEnemyForces;

/**
 * @var Npc                 $npc
 * @var NpcEnemyForces      $npcEnemyForces
 */
$npcEnemyForces = $npcEnemyForces ?? null;
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$npc, $npcEnemyForces],
    'showAds' => false,
    'title' => __('view_admin.npcenemyforces.edit.title', ['name' => $npc->name]),
])
@section('header-title')
    {{ __('view_admin.npcenemyforces.edit.header', ['name' => $npc->name]) }}
@endsection

@section('content')
    @isset($npcEnemyForces)
        {{ Form::model($npc, ['route' => ['admin.npc.npcenemyforces.update', $npc, $npcEnemyForces], 'autocomplete' => 'off', 'method' => 'patch']) }}
    @else
        {{ Form::model($npc, ['route' => ['admin.npc.npcenemyforces.savenew', $npc], 'autocomplete' => 'off', 'method' => 'post']) }}
    @endisset

    <div class="form-group{{ $errors->has('mapping_version_id') ? ' has-error' : '' }}">
        {!! Form::label('mapping_version_id', __('view_admin.npcenemyforces.edit.mapping_version'), [], false) !!}
        <span class="form-required">*</span>
        @include('common.mappingversion.select', [
            'id' => 'mapping_version_id',
            'dungeons' => $npc->dungeons,
            'selected' => $npcEnemyForces?->mapping_version_id
        ])
        @include('common.forms.form-error', ['key' => 'mapping_version_id'])
    </div>

    <div class="form-group{{ $errors->has('enemy_forces') ? ' has-error' : '' }}">
        {!! Form::label('enemy_forces', __('view_admin.npcenemyforces.edit.enemy_forces'), [], false) !!}
        <span class="form-required">*</span>
        {!! Form::number('enemy_forces', $npcEnemyForces?->enemy_forces, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'enemy_forces'])
    </div>

    <div class="form-group{{ $errors->has('enemy_forces_teeming') ? ' has-error' : '' }}">
        {!! Form::label('enemy_forces_teeming', __('view_admin.npcenemyforces.edit.enemy_forces_teeming'), [], false) !!}
        {!! Form::number('enemy_forces_teeming', $npcEnemyForces?->enemy_forces_teeming ?? '', ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'enemy_forces_teeming'])
    </div>

    <div class="form-group">
        {!! Form::submit(__('view_admin.npcenemyforces.edit.submit'), ['class' => 'btn btn-info', 'name' => 'submit', 'value' => 'submit']) !!}
    </div>

    {!! Form::close() !!}
@endsection
