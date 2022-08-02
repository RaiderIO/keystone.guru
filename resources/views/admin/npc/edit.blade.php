@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$npc ?? null],
    'showAds' => false,
    'title' => isset($npc) ? __('views/admin.npc.edit.title_edit') : __('views/admin.npc.edit.title_new')
])
@section('header-title')
    {{ isset($npc) ? __('views/admin.npc.edit.header_edit') : __('views/admin.npc.edit.header_new') }}
@endsection
<?php
/**
 * @var $model \App\Models\Npc
 * @var $floor \App\Models\Floor
 * @var $classifications array
 * @var $spells \App\Models\Spell[]
 * @var $bolsteringNpcs array
 */
?>

@section('content')
    @isset($npc)
        {{ Form::model($npc, ['route' => ['admin.npc.update', $npc->id], 'autocomplete' => 'off', 'method' => 'patch', 'files' => true]) }}
    @else
        {{ Form::open(['route' => 'admin.npc.savenew', 'autocomplete' => 'off', 'files' => true]) }}
    @endisset

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('name', __('views/admin.npc.edit.name'), [], false) !!}
        <span class="form-required">*</span>
        {!! Form::text('name', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    <div class="form-group{{ $errors->has('id') ? ' has-error' : '' }}">
        {!! Form::label('id', __('views/admin.npc.edit.game_id'), [], false) !!}
        <span class="form-required">*</span>
        {!! Form::text('id', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'id'])
    </div>

    @include('common.dungeon.select', ['activeOnly' => false])

    <div class="form-group{{ $errors->has('classification_id') ? ' has-error' : '' }}">
        {!! Form::label('classification_id', __('views/admin.npc.edit.classification'), [], false) !!}
        <span class="form-required">*</span>
        {!! Form::select('classification_id', $classifications, null, ['class' => 'form-control selectpicker']) !!}
        @include('common.forms.form-error', ['key' => 'classification_id'])
    </div>

    <div class="form-group{{ $errors->has('aggressiveness') ? ' has-error' : '' }}">
        {!! Form::label('aggressiveness', __('views/admin.npc.edit.aggressiveness'), [], false) !!}
        <span class="form-required">*</span>
        {!! Form::select('aggressiveness', array_combine(config('keystoneguru.aggressiveness'), config('keystoneguru.aggressiveness_pretty')), null, ['class' => 'form-control selectpicker']) !!}
        @include('common.forms.form-error', ['key' => 'aggressiveness'])
    </div>

    <div class="form-group{{ $errors->has('npc_class_id') ? ' has-error' : '' }}">
        {!! Form::label('npc_class_id', __('views/admin.npc.edit.class'), [], false) !!}
        <span class="form-required">*</span>
        {!! Form::select('npc_class_id', \App\Models\NpcClass::pluck('name', 'id'), null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'npc_class_id'])
    </div>

    <div class="form-group{{ $errors->has('base_health') ? ' has-error' : '' }}">
        {!! Form::label('base_health', __('views/admin.npc.edit.base_health'), [], false) !!}
        <span class="form-required">*</span>
        {!! Form::text('base_health', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'base_health'])
    </div>

    <div class="form-group{{ $errors->has('enemy_forces') ? ' has-error' : '' }}">
        {!! Form::label('enemy_forces', __('views/admin.npc.edit.enemy_forces')) !!}
        {!! Form::text('enemy_forces', isset($npc) ? $npc->enemy_forces : -1, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'enemy_forces'])
    </div>

    <div class="form-group{{ $errors->has('enemy_forces') ? ' has-error' : '' }}">
        {!! Form::label('enemy_forces_teeming', __('views/admin.npc.edit.enemy_forces_teeming')) !!}
        {!! Form::text('enemy_forces_teeming', isset($npc) ? $npc->enemy_forces_teeming : -1, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'enemy_forces_teeming'])
    </div>

    <div class="form-group">
        <div class="row">
            <div class="col">
                <div class="{{ $errors->has('dangerous') ? ' has-error' : '' }}">
                    {!! Form::label('dangerous', __('views/admin.npc.edit.dangerous')) !!}
                    {!! Form::checkbox('dangerous', 1, isset($npc) ? $npc->dangerous : 1, ['class' => 'form-control left_checkbox']) !!}
                    @include('common.forms.form-error', ['key' => 'dangerous'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('truesight') ? ' has-error' : '' }}">
                    {!! Form::label('truesight', __('views/admin.npc.edit.truesight')) !!}
                    {!! Form::checkbox('truesight', 1, isset($npc) ? $npc->truesight : 1, ['class' => 'form-control left_checkbox']) !!}
                    @include('common.forms.form-error', ['key' => 'truesight'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('bursting') ? ' has-error' : '' }}">
                    {!! Form::label('bursting', __('views/admin.npc.edit.bursting')) !!}
                    {!! Form::checkbox('bursting', 1, isset($npc) ? $npc->bursting : 1, ['class' => 'form-control left_checkbox']) !!}
                    @include('common.forms.form-error', ['key' => 'bursting'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('bolstering') ? ' has-error' : '' }}">
                    {!! Form::label('bolstering', __('views/admin.npc.edit.bolstering')) !!}
                    {!! Form::checkbox('bolstering', 1, isset($npc) ? $npc->bolstering : 1, ['class' => 'form-control left_checkbox']) !!}
                    @include('common.forms.form-error', ['key' => 'bolstering'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('sanguine') ? ' has-error' : '' }}">
                    {!! Form::label('sanguine', __('views/admin.npc.edit.sanguine')) !!}
                    {!! Form::checkbox('sanguine', 1, isset($npc) ? $npc->sanguine : 1, ['class' => 'form-control left_checkbox']) !!}
                    @include('common.forms.form-error', ['key' => 'sanguine'])
                </div>
            </div>
        </div>
    </div>

    <div class="form-group">
        {!! Form::label('bolstering_whitelist_npcs[]', __('views/admin.npc.edit.bolstering_npc_whitelist'), [], false) !!}
        {!! Form::select('bolstering_whitelist_npcs[]', $bolsteringNpcs, isset($npc) ? $npc->npcbolsteringwhitelists->pluck(['whitelist_npc_id'])->toArray() : [], [
                'class' => 'form-control selectpicker',
                'multiple' => 'multiple',
                'data-live-search' => 'true',
                'data-selected-text-format' => 'count > 1',
                'data-count-selected-text' => __('views/admin.npc.edit.bolstering_npc_whitelist_count'),
            ]) !!}
    </div>

    <div class="form-group">
        {!! Form::label('spells[]', __('views/admin.npc.edit.spells'), [], false) !!}
        @php($selectedSpells = isset($npc) ? $npc->spells->pluck(['id'])->toArray() : [])
        <select class="form-control selectpicker" name="spells[]" multiple="multiple"
                data-live-search="true" data-selected-text-format="count > 1"
                data-count-selected-text="{{ __('views/admin.npc.edit.spells_count') }}">
            @foreach($spells as $spell)
                <option value="{{$spell->id}}" {{in_array($spell->id, $selectedSpells) ? 'selected="selected"' : ''}}
                data-content="<span><img src='{{$spell->icon_url}}' width='24px'/> {{$spell->name}} ({{$spell->id}}) </span>">
                </option>
            @endforeach
        </select>
    </div>



    <div>
        {!! Form::submit(__('views/admin.npc.edit.submit'), ['class' => 'btn btn-info', 'name' => 'submit', 'value' => 'submit']) !!}
        @isset($npc)
            <div class="float-right">
                {!! Form::submit(__('views/admin.npc.edit.save_as_new_npc'), ['class' => 'btn btn-info', 'name' => 'submit', 'value' => 'saveasnew']) !!}
            </div>
        @endisset
    </div>

    {!! Form::close() !!}
@endsection
