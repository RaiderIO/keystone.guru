@extends('layouts.app', ['showAds' => false, 'title' => __('Edit Npc')])
@section('header-title')
    {{ $headerTitle }}
@endsection
@section('header-addition')
    <a href="{{ route('admin.npcs') }}" class="btn btn-info text-white float-right" role="button">
        <i class="fas fa-backward"></i> {{ __('Npc list') }}
    </a>
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
    @isset($model)
        {{ Form::model($model, ['route' => ['admin.npc.update', $model->id], 'autocomplete' => 'off', 'method' => 'patch', 'files' => true]) }}
    @else
        {{ Form::open(['route' => 'admin.npc.savenew', 'autocomplete' => 'off', 'files' => true]) }}
    @endisset

    <div class="form-group{{ $errors->has('portrait') ? ' has-error' : '' }}">
        {!! Form::label('id', __('Portrait (temp)'), [], false) !!}
        {!! Form::file('portrait', ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'portrait'])
    </div>

    <div class="form-group{{ $errors->has('id') ? ' has-error' : '' }}">
        {!! Form::label('id', __('Game ID') . '<span class="form-required">*</span>', [], false) !!}
        {!! Form::text('id', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'id'])
    </div>

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('name', __('Name') . '<span class="form-required">*</span>', [], false) !!}
        {!! Form::text('name', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    @include('common.dungeon.select', ['activeOnly' => false, 'showShadowlandsPromo' => false])

    <div class="form-group{{ $errors->has('classification_id') ? ' has-error' : '' }}">
        {!! Form::label('classification_id', __('Classification') . '<span class="form-required">*</span>', [], false) !!}
        {!! Form::select('classification_id', $classifications, null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'classification_id'])
    </div>

    <div class="form-group{{ $errors->has('aggressiveness') ? ' has-error' : '' }}">
        {!! Form::label('aggressiveness', __('Aggressiveness') . '<span class="form-required">*</span>', [], false) !!}
        {!! Form::select('aggressiveness', array_combine(config('keystoneguru.aggressiveness'), config('keystoneguru.aggressiveness_pretty')), null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'aggressiveness'])
    </div>

    <div class="form-group{{ $errors->has('npc_class_id') ? ' has-error' : '' }}">
        {!! Form::label('npc_class_id', __('Class') . '<span class="form-required">*</span>', [], false) !!}
        {!! Form::select('npc_class_id', \App\Models\NpcClass::pluck('name', 'id'), null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'npc_class_id'])
    </div>

    <div class="form-group{{ $errors->has('base_health') ? ' has-error' : '' }}">
        {!! Form::label('base_health', __('Base health') . '<span class="form-required">*</span>', [], false) !!}
        {!! Form::text('base_health', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'base_health'])
    </div>

    <div class="form-group{{ $errors->has('enemy_forces') ? ' has-error' : '' }}">
        {!! Form::label('enemy_forces', __('Enemy forces (-1 for unknown)')) !!}
        {!! Form::text('enemy_forces', isset($model) ? $model->enemy_forces : -1, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'enemy_forces'])
    </div>

    <div class="form-group{{ $errors->has('enemy_forces') ? ' has-error' : '' }}">
        {!! Form::label('enemy_forces_teeming', __('Enemy forces teeming (-1 for same as normal)')) !!}
        {!! Form::text('enemy_forces_teeming', isset($model) ? $model->enemy_forces_teeming : -1, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'enemy_forces_teeming'])
    </div>

    <div class="form-group">
        <div class="row">
            <div class="col">
                <div class="{{ $errors->has('dangerous') ? ' has-error' : '' }}">
                    {!! Form::label('dangerous', __('Dangerous')) !!}
                    {!! Form::checkbox('dangerous', 1, isset($model) ? $model->dangerous : 1, ['class' => 'form-control left_checkbox']) !!}
                    @include('common.forms.form-error', ['key' => 'dangerous'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('truesight') ? ' has-error' : '' }}">
                    {!! Form::label('truesight', __('Truesight')) !!}
                    {!! Form::checkbox('truesight', 1, isset($model) ? $model->truesight : 1, ['class' => 'form-control left_checkbox']) !!}
                    @include('common.forms.form-error', ['key' => 'truesight'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('bursting') ? ' has-error' : '' }}">
                    {!! Form::label('bursting', __('Bursting')) !!}
                    {!! Form::checkbox('bursting', 1, isset($model) ? $model->bursting : 1, ['class' => 'form-control left_checkbox']) !!}
                    @include('common.forms.form-error', ['key' => 'bursting'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('bolstering') ? ' has-error' : '' }}">
                    {!! Form::label('bolstering', __('Bolstering')) !!}
                    {!! Form::checkbox('bolstering', 1, isset($model) ? $model->bolstering : 1, ['class' => 'form-control left_checkbox']) !!}
                    @include('common.forms.form-error', ['key' => 'bolstering'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('sanguine') ? ' has-error' : '' }}">
                    {!! Form::label('sanguine', __('Sanguine')) !!}
                    {!! Form::checkbox('sanguine', 1, isset($model) ? $model->sanguine : 1, ['class' => 'form-control left_checkbox']) !!}
                    @include('common.forms.form-error', ['key' => 'sanguine'])
                </div>
            </div>
        </div>
    </div>

    <div class="form-group">
        {!! Form::label('bolstering_whitelist_npcs[]', __('Bolstering NPC Whitelist'), [], false) !!}
        {!! Form::select('bolstering_whitelist_npcs[]', $bolsteringNpcs, isset($model) ? $model->npcbolsteringwhitelists->pluck(['whitelist_npc_id'])->toArray() : [], [
                'class' => 'form-control selectpicker',
                'multiple' => 'multiple',
                'data-live-search' => 'true',
                'data-selected-text-format' => 'count > 1',
                'data-count-selected-text' => __('{0} NPCs'),
            ]) !!}
    </div>

    <div class="form-group">
        {!! Form::label('spells[]', __('Spells'), [], false) !!}
        @php($selectedSpells = isset($model) ? $model->spells->pluck(['id'])->toArray() : [])
         <select class="form-control selectpicker" name="spells[]" multiple="multiple"
                 data-live-search="true" data-selected-text-format="count > 1" data-count-selected-text="{{ __('{0} Spells') }}">
             @foreach($spells as $spell)
             <option value="{{$spell->id}}" {{in_array($spell->id, $selectedSpells) ? 'selected="selected"' : ''}}
                data-content="<span><img src='{{$spell->getIconUrl()}}' width='24px'/> {{$spell->name}} </span>">
                 {{$spell->name}}
             </option>
             @endforeach
         </select>
    </div>



    <div>
        {!! Form::submit(__('Submit'), ['class' => 'btn btn-info', 'name' => 'submit', 'value' => 'submit']) !!}
        @isset($model)
            <div class="float-right">
                {!! Form::submit(__('Save as new npc'), ['class' => 'btn btn-info', 'name' => 'submit', 'value' => 'saveasnew']) !!}
            </div>
        @endisset
    </div>

    {!! Form::close() !!}
@endsection