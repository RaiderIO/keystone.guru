<?php
/**
 * @var $npc \App\Models\Npc
 * @var $npcEnemyForces \App\Models\Npc\NpcEnemyForces
 */
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$npc, $npcEnemyForces],
    'showAds' => false,
    'title' => __('views/admin.npcenemyforces.edit.title_edit', ['name' => $npc->name]),
])
@section('header-title')
    {{ __('views/admin.npcenemyforces.edit.header_edit', ['name' => $npc->name]) }}
@endsection

@section('content')
    {{ Form::model($npc, ['route' => ['admin.npcenemyforces.update', $npc, $npcEnemyForces], 'autocomplete' => 'off', 'method' => 'patch', 'files' => true]) }}

    <div class="form-group{{ $errors->has('enemy_forces') ? ' has-error' : '' }}">
        {!! Form::label('enemy_forces', __('views/admin.npcenemyforces.edit.enemy_forces'), [], false) !!}
        <span class="form-required">*</span>
        {!! Form::text('enemy_forces', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'enemy_forces'])
    </div>

    <div class="form-group{{ $errors->has('enemy_forces_teeming') ? ' has-error' : '' }}">
        {!! Form::label('enemy_forces_teeming', __('views/admin.npcenemyforces.edit.enemy_forces_teeming'), [], false) !!}
        <span class="form-required">*</span>
        {!! Form::text('enemy_forces_teeming', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'enemy_forces_teeming'])
    </div>

    <div class="form-group">
        {!! Form::submit(__('views/admin.npcenemyforces.edit.submit'), ['class' => 'btn btn-info', 'name' => 'submit', 'value' => 'submit']) !!}
    </div>

    {!! Form::close() !!}
@endsection
