<?php

use App\Models\Floor\Floor;
use App\Models\Spell\Spell;

/**
 * @var Spell $spell
 * @var Floor $floor
 * @var array<string> $categories
 * @var array<string> $dispelTypes
 * @var array<string> $cooldownGroups
 */
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$spell ?? null],
    'showAds' => false,
    'title' => isset($spell) ? __('view_admin.spell.edit.title_edit') : __('view_admin.spell.edit.title_new'),
    ])
@section('header-title')
    {{ isset($spell) ? __('view_admin.spell.edit.header_edit') : __('view_admin.spell.edit.header_new') }}
@endsection

@section('content')
    @isset($spell)
        {{ Form::model($spell, ['route' => ['admin.spell.update', $spell->id], 'autocomplete' => 'off', 'method' => 'patch', 'files' => true]) }}
    @else
        {{ Form::open(['route' => 'admin.spell.savenew', 'autocomplete' => 'off', 'files' => true]) }}
    @endisset
    <div class="form-group{{ $errors->has('id') ? ' has-error' : '' }}">
        {!! Form::label('id', __('view_admin.spell.edit.game_id') . '<span class="form-required">*</span>', [], false) !!}
        {!! Form::text('id', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'id'])
    </div>

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('name', __('view_admin.spell.edit.name') . '<span class="form-required">*</span>', [], false) !!}
        {!! Form::text('name', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    <div class="form-group{{ $errors->has('icon_name') ? ' has-error' : '' }}">
        {!! Form::label('icon_name', __('view_admin.spell.edit.icon_name') . '<span class="form-required">*</span>', [], false) !!}
        {!! Form::text('icon_name', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'icon_name'])
    </div>

    <div class="form-group{{ $errors->has('category') ? ' has-error' : '' }}">
        {!! Form::label('category', __('view_admin.spell.edit.category') . '<span class="form-required">*</span>', [], false) !!}
        {!! Form::select('category', $categories, null, ['class' => 'form-control selectpicker']) !!}
        @include('common.forms.form-error', ['key' => 'category'])
    </div>

    <div class="form-group{{ $errors->has('dispel_type') ? ' has-error' : '' }}">
        {!! Form::label('dispel_type', __('view_admin.spell.edit.dispel_type') . '<span class="form-required">*</span>', [], false) !!}
        <?php $dispelTypes = array_merge(['None'], $dispelTypes); ?>
        {!! Form::select('dispel_type', array_combine($dispelTypes, $dispelTypes), null, ['class' => 'form-control selectpicker']) !!}
        @include('common.forms.form-error', ['key' => 'dispel_type'])
    </div>

    <div class="form-group{{ $errors->has('cooldown_group') ? ' has-error' : '' }}">
        {!! Form::label('cooldown_group', __('view_admin.spell.edit.cooldown_group') . '<span class="form-required">*</span>', [], false) !!}
        {!! Form::select('cooldown_group', $cooldownGroups, null, ['class' => 'form-control selectpicker']) !!}
        @include('common.forms.form-error', ['key' => 'cooldown_group'])
    </div>

    <div class="form-group{{ $errors->has('schools') ? ' has-error' : '' }}">
        {!! Form::label('schools[]', __('view_admin.spell.edit.schools'), [], false) !!}
        {!! Form::select('schools[]', array_flip($schools), isset($spell) ? $spell->getSchoolsAsArray() : null, ['class' => 'form-control selectpicker', 'multiple' => 'multiple', 'size' => count($schools)]) !!}
        @include('common.forms.form-error', ['key' => 'schools'])
    </div>

    <div class="form-group{{ $errors->has('aura') ? ' has-error' : '' }}">
        {!! Form::label('aura', __('view_admin.spell.edit.aura')) !!}
        {!! Form::checkbox('aura', 1, isset($spell) ? $spell->aura : 1, ['class' => 'form-control left_checkbox']) !!}
        @include('common.forms.form-error', ['key' => 'aura'])
    </div>

    <div class="form-group{{ $errors->has('selectable') ? ' has-error' : '' }}">
        {!! Form::label('selectable', __('view_admin.spell.edit.selectable')) !!}
        {!! Form::checkbox('selectable', 1, isset($spell) ? $spell->selectable : 1, ['class' => 'form-control left_checkbox']) !!}
        @include('common.forms.form-error', ['key' => 'selectable'])
    </div>

    <div>
        {!! Form::submit(__('view_admin.spell.edit.submit'), ['class' => 'btn btn-info', 'name' => 'submit', 'value' => 'submit']) !!}
        @isset($spell)
            <div class="float-right">
                {!! Form::submit(__('view_admin.spell.edit.save_as_new_spell'), ['class' => 'btn btn-info', 'name' => 'submit', 'value' => 'saveasnew']) !!}
            </div>
        @endisset
    </div>

    {!! Form::close() !!}
@endsection
