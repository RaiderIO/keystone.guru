<?php

use App\Models\Floor\Floor;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClass;
use App\Models\Npc\NpcType;
use App\Models\Spell\Spell;

/**
 * @var Npc     $npc
 * @var Floor   $floor
 * @var array   $classifications
 * @var Spell[] $spells
 * @var array   $bolsteringNpcs
 */
?>

@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$npc ?? null],
    'showAds' => false,
    'title' => isset($npc) ? __('view_admin.npc.edit.title_edit', ['name' => $npc->name]) : __('view_admin.npc.edit.title_new'),
])

@section('header-title')
    {{ isset($npc) ? __('view_admin.npc.edit.header_edit', ['name' => $npc->name]) : __('view_admin.npc.edit.header_new') }}
@endsection
@section('content')
    @isset($npc)
        {{ Form::model($npc, ['route' => ['admin.npc.update', $npc->id], 'autocomplete' => 'off', 'method' => 'patch', 'files' => true]) }}
    @else
        {{ Form::open(['route' => 'admin.npc.savenew', 'autocomplete' => 'off', 'files' => true]) }}
    @endisset

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('name', __('view_admin.npc.edit.name'), [], false) !!}
        <span class="form-required">*</span>
        {!! Form::text('name', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    <div class="form-group{{ $errors->has('id') ? ' has-error' : '' }}">
        {!! Form::label('id', __('view_admin.npc.edit.game_id'), [], false) !!}
        <span class="form-required">*</span>
        {!! Form::text('id', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'id'])
    </div>

    @include('common.dungeon.select', [
        'name' => 'dungeon_ids[]',
        'selected' => isset($npc) ? $npc->dungeons->pluck('id')->toArray() : [],
        'multiple' => true,
        'showAll' => false,
        'activeOnly' => false,
        'ignoreGameVersion' => true
    ])

    <div class="form-group{{ $errors->has('classification_id') ? ' has-error' : '' }}">
        {!! Form::label('classification_id', __('view_admin.npc.edit.classification'), [], false) !!}
        <span class="form-required">*</span>
        {!! Form::select('classification_id', $classifications, null, ['class' => 'form-control selectpicker']) !!}
        @include('common.forms.form-error', ['key' => 'classification_id'])
    </div>

    <div class="form-group{{ $errors->has('aggressiveness') ? ' has-error' : '' }}">
        {!! Form::label('aggressiveness', __('view_admin.npc.edit.aggressiveness'), [], false) !!}
        <span class="form-required">*</span>
        <?php
        $aggressivenessSelect = [];
        foreach (Npc::ALL_AGGRESSIVENESS as $aggressiveness) {
            $aggressivenessSelect[$aggressiveness] = __(sprintf('npcaggressiveness.%s', $aggressiveness));
        }
        ?>
        {!! Form::select('aggressiveness', $aggressivenessSelect, null, ['class' => 'form-control selectpicker']) !!}
        @include('common.forms.form-error', ['key' => 'aggressiveness'])
    </div>

    <div class="form-group{{ $errors->has('npc_type_id') ? ' has-error' : '' }}">
        {!! Form::label('npc_class_id', __('view_admin.npc.edit.type'), [], false) !!}
        <span class="form-required">*</span>
        {!! Form::select('npc_type_id', NpcType::pluck('type', 'id'), null, ['class' => 'form-control selectpicker']) !!}
        @include('common.forms.form-error', ['key' => 'npc_type_id'])
    </div>

    <div class="form-group{{ $errors->has('npc_class_id') ? ' has-error' : '' }}">
        {!! Form::label('npc_class_id', __('view_admin.npc.edit.class'), [], false) !!}
        <span class="form-required">*</span>
        {!! Form::select('npc_class_id', NpcClass::pluck('name', 'id')->mapWithKeys(static fn($name, $id) => [$id => __($name)]), null,
                        ['class' => 'form-control selectpicker']) !!}
        @include('common.forms.form-error', ['key' => 'npc_class_id'])
    </div>

    <div class="form-group{{ $errors->has('level') ? ' has-error' : '' }}">
        {!! Form::label('level', __('view_admin.npc.edit.level')) !!}
        {!! Form::number('level', $npc->level ?? null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'level'])
    </div>

    <div class="form-group">
        <div class="row">
            <div class="col">
                <div class="{{ $errors->has('dangerous') ? ' has-error' : '' }}">
                    {!! Form::label('dangerous', __('view_admin.npc.edit.dangerous')) !!}
                    {!! Form::checkbox('dangerous', 1, isset($npc) ? $npc->dangerous : 0, ['class' => 'form-control left_checkbox']) !!}
                    @include('common.forms.form-error', ['key' => 'dangerous'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('truesight') ? ' has-error' : '' }}">
                    {!! Form::label('truesight', __('view_admin.npc.edit.truesight')) !!}
                    {!! Form::checkbox('truesight', 1, isset($npc) ? $npc->truesight : 0, ['class' => 'form-control left_checkbox']) !!}
                    @include('common.forms.form-error', ['key' => 'truesight'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('bursting') ? ' has-error' : '' }}">
                    {!! Form::label('bursting', __('view_admin.npc.edit.bursting')) !!}
                    {!! Form::checkbox('bursting', 1, isset($npc) ? $npc->bursting : 1, ['class' => 'form-control left_checkbox']) !!}
                    @include('common.forms.form-error', ['key' => 'bursting'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('bolstering') ? ' has-error' : '' }}">
                    {!! Form::label('bolstering', __('view_admin.npc.edit.bolstering')) !!}
                    {!! Form::checkbox('bolstering', 1, isset($npc) ? $npc->bolstering : 1, ['class' => 'form-control left_checkbox']) !!}
                    @include('common.forms.form-error', ['key' => 'bolstering'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('sanguine') ? ' has-error' : '' }}">
                    {!! Form::label('sanguine', __('view_admin.npc.edit.sanguine')) !!}
                    {!! Form::checkbox('sanguine', 1, isset($npc) ? $npc->sanguine : 1, ['class' => 'form-control left_checkbox']) !!}
                    @include('common.forms.form-error', ['key' => 'sanguine'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('runs_away_in_fear') ? ' has-error' : '' }}">
                    {!! Form::label('runs_away_in_fear', __('view_admin.npc.edit.runs_away_in_fear')) !!}
                    {!! Form::checkbox('runs_away_in_fear', 1, isset($npc) ? $npc->runs_away_in_fear : 0, ['class' => 'form-control left_checkbox']) !!}
                    @include('common.forms.form-error', ['key' => 'runs_away_in_fear'])
                </div>
            </div>
        </div>
    </div>

    <div class="form-group">
        {!! Form::label('bolstering_whitelist_npcs[]', __('view_admin.npc.edit.bolstering_npc_whitelist'), [], false) !!}
        {!! Form::select('bolstering_whitelist_npcs[]', $bolsteringNpcs, isset($npc) ? $npc->npcbolsteringwhitelists->pluck(['whitelist_npc_id'])->toArray() : [], [
                'class' => 'form-control selectpicker',
                'multiple' => 'multiple',
                'data-live-search' => 'true',
                'data-selected-text-format' => 'count > 1',
                'data-count-selected-text' => __('view_admin.npc.edit.bolstering_npc_whitelist_count'),
            ]) !!}
    </div>

    <div class="form-group">
        {!! Form::label('spells[]', __('view_admin.npc.edit.spells'), [], false) !!}
        @php($selectedSpells = isset($npc) ? $npc->spells(false)->get()->pluck(['id'])->toArray() : [])
        <!--suppress HtmlFormInputWithoutLabel -->
        <select class="form-control" name="spells[]" multiple="multiple"
                data-live-search="true" data-selected-text-format="count > 1"
                data-count-selected-text="{{ __('view_admin.npc.edit.spells_count') }}">
            @foreach($spells as $spell)
                <option value="{{$spell->id}}" {{in_array($spell->id, $selectedSpells) ? 'selected="selected"' : ''}}
                data-content="<span><img src='{{$spell->icon_url}}' width='24px'/> {{$spell->name}} ({{$spell->id}}) </span>"
                >
                </option>
            @endforeach
        </select>
    </div>



    <div class="form-group">
        {!! Form::submit(__('view_admin.npc.edit.submit'), ['class' => 'btn btn-info', 'name' => 'submit', 'value' => 'submit']) !!}
        @isset($npc)
            <div class="float-right">
                {!! Form::submit(__('view_admin.npc.edit.save_as_new_npc'), ['class' => 'btn btn-info', 'name' => 'submit', 'value' => 'saveasnew']) !!}
            </div>
        @endisset
    </div>

    {!! Form::close() !!}

    @isset($npc)
        <div class="form-group">
            @include('admin.npc.npchealth', ['npc' => $npc])
        </div>

        <div class="form-group">
            @include('admin.npc.enemyforces', ['npc' => $npc])
        </div>
    @endisset
@endsection
