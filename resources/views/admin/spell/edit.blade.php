@extends('layouts.sitepage', ['breadcrumbsParams' => [$spell ?? null], 'showAds' => false, 'title' => __('Edit Spell')])
@section('header-title')
    {{ $headerTitle }}
@endsection
<?php
/**
 * @var $spell \App\Models\Spell
 * @var $floor \App\Models\Floor
 * @var $dispelTypes array
 */
?>

@section('content')
    @isset($spell)
        {{ Form::model($spell, ['route' => ['admin.spell.update', $spell->id], 'autocomplete' => 'off', 'method' => 'patch', 'files' => true]) }}
    @else
        {{ Form::open(['route' => 'admin.spell.savenew', 'autocomplete' => 'off', 'files' => true]) }}
    @endisset
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

    <div class="form-group{{ $errors->has('icon_name') ? ' has-error' : '' }}">
        {!! Form::label('icon_name', __('Icon name') . '<span class="form-required">*</span>', [], false) !!}
        {!! Form::text('icon_name', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'icon_name'])
    </div>

    <div class="form-group{{ $errors->has('dispel_type') ? ' has-error' : '' }}">
        {!! Form::label('dispel_type', __('Dispel type') . '<span class="form-required">*</span>', [], false) !!}
        @php($dispelTypes = array_merge(['None'], $dispelTypes))
        {!! Form::select('dispel_type', array_combine($dispelTypes, $dispelTypes), null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'dispel_type'])
    </div>

    <div class="form-group{{ $errors->has('schools') ? ' has-error' : '' }}">
        {!! Form::label('schools[]', __('Schools'), [], false) !!}
        {!! Form::select('schools[]', array_flip($schools), isset($spell) ? $spell->getSchoolsAsArray() : null, ['class' => 'form-control', 'multiple' => 'multiple', 'size' => count($schools)]) !!}
        @include('common.forms.form-error', ['key' => 'schools'])
    </div>

    <div class="form-group{{ $errors->has('aura') ? ' has-error' : '' }}">
        {!! Form::label('aura', __('Aura')) !!}
        {!! Form::checkbox('aura', 1, isset($spell) ? $spell->aura : 1, ['class' => 'form-control left_checkbox']) !!}
        @include('common.forms.form-error', ['key' => 'aura'])
    </div>

    <div>
        {!! Form::submit(__('Submit'), ['class' => 'btn btn-info', 'name' => 'submit', 'value' => 'submit']) !!}
        @isset($spell)
            <div class="float-right">
                {!! Form::submit(__('Save as new spell'), ['class' => 'btn btn-info', 'name' => 'submit', 'value' => 'saveasnew']) !!}
            </div>
        @endisset
    </div>

    {!! Form::close() !!}
@endsection