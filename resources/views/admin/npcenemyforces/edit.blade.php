<?php
/**
 * @var $npc \App\Models\Npc\Npc
 * @var $npcEnemyForces \App\Models\Npc\NpcEnemyForces
 */
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
    {{ Form::model($npc, ['route' => ['admin.npcenemyforces.update', $npc, $npcEnemyForces], 'autocomplete' => 'off', 'method' => 'patch', 'files' => true]) }}

    <div class="form-group{{ $errors->has('enemy_forces') ? ' has-error' : '' }}">
        {!! Form::label('enemy_forces', __('view_admin.npcenemyforces.edit.enemy_forces'), [], false) !!}
        <span class="form-required">*</span>
        {!! Form::number('enemy_forces', $npcEnemyForces->enemy_forces, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'enemy_forces'])
    </div>

    <div class="form-group{{ $errors->has('enemy_forces_teeming') ? ' has-error' : '' }}">
        {!! Form::label('enemy_forces_teeming', __('view_admin.npcenemyforces.edit.enemy_forces_teeming'), [], false) !!}
        {!! Form::number('enemy_forces_teeming', $npcEnemyForces->enemy_forces_teeming ?? '', ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'enemy_forces_teeming'])
    </div>

    <div class="form-group">
        {!! Form::submit(__('view_admin.npcenemyforces.edit.submit'), ['class' => 'btn btn-info', 'name' => 'submit', 'value' => 'submit']) !!}
    </div>

    {!! Form::close() !!}
@endsection
