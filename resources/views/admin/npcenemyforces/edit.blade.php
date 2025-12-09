<?php

use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcEnemyForces;

/**
 * @var Npc                 $npc
 * @var NpcEnemyForces      $npcEnemyForces
 */
$npcEnemyForces ??= null;
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$npc, $npcEnemyForces],
    'showAds' => false,
    'title' => __('view_admin.npcenemyforces.edit.title', ['name' => __($npc->name)]),
])
@section('header-title')
    {{ __('view_admin.npcenemyforces.edit.header', ['name' => __($npc->name)]) }}
@endsection

@section('content')
    @isset($npcEnemyForces)
        {{ html()->modelForm($npc, 'PATCH', route('admin.npc.npcenemyforces.update', [$npc, $npcEnemyForces]))->attribute('autocomplete', 'off')->open() }}
    @else
        {{ html()->modelForm($npc, 'POST', route('admin.npc.npcenemyforces.savenew', $npc))->attribute('autocomplete', 'off')->open() }}
    @endisset

    <div class="form-group{{ $errors->has('mapping_version_id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.npcenemyforces.edit.mapping_version'), 'mapping_version_id') }}
        <span class="form-required">*</span>
        @include('common.mappingversion.select', [
            'id' => 'mapping_version_id',
            'dungeons' => $npc->dungeons,
            'selected' => $npcEnemyForces?->mapping_version_id
        ])
        @include('common.forms.form-error', ['key' => 'mapping_version_id'])
    </div>

    <div class="form-group{{ $errors->has('enemy_forces') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.npcenemyforces.edit.enemy_forces'), 'enemy_forces') }}
        <span class="form-required">*</span>
        {{ html()->number('enemy_forces', $npcEnemyForces?->enemy_forces)->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'enemy_forces'])
    </div>

    <div class="form-group{{ $errors->has('enemy_forces_teeming') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.npcenemyforces.edit.enemy_forces_teeming'), 'enemy_forces_teeming') }}
        {{ html()->number('enemy_forces_teeming', $npcEnemyForces?->enemy_forces_teeming ?? '')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'enemy_forces_teeming'])
    </div>

    <div class="form-group">
        {{ html()->input('submit')->value(__('view_admin.npcenemyforces.edit.submit'))->class('btn btn-info')->name('submit') }}
    </div>

    {{ html()->closeModelForm() }}
@endsection
