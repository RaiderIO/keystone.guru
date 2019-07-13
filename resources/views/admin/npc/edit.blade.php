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
 */
?>

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['admin.npc.update', $model->id], 'autocomplete' => 'off', 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => 'admin.npc.savenew', 'autocomplete' => 'off']) }}
    @endisset

    <div class="form-group{{ $errors->has('id') ? ' has-error' : '' }}">
        {!! Form::label('id', __('Game ID') . "*") !!}
        {!! Form::text('id', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'id'])
    </div>

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('name', __('Name') . "*") !!}
        {!! Form::text('name', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    <div class="form-group">
        {!! Form::label('dungeon_id', __('Select dungeon') . "*") !!}
        {!! Form::select('dungeon_id', [-1 => __('All dungeons')] + \App\Models\Dungeon::active()->get()->pluck('name', 'id')->toArray(), null, ['class' => 'form-control']) !!}
    </div>

    <div class="form-group{{ $errors->has('classification_id') ? ' has-error' : '' }}">
        {!! Form::label('classification_id', __('Classification') . "*") !!}
        {!! Form::select('classification_id', $classifications, null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'classification_id'])
    </div>

    <div class="form-group{{ $errors->has('aggressiveness') ? ' has-error' : '' }}">
        {!! Form::label('aggressiveness', __('Aggressiveness') . "*") !!}
        {!! Form::select('aggressiveness', array_combine(config('keystoneguru.aggressiveness'), config('keystoneguru.aggressiveness_pretty')), null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'classification_id'])
    </div>

    <div class="form-group{{ $errors->has('base_health') ? ' has-error' : '' }}">
        {!! Form::label('base_health', __('Base health') . "*") !!}
        {!! Form::text('base_health', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'base_health'])
    </div>

    <div class="form-group{{ $errors->has('enemy_forces') ? ' has-error' : '' }}">
        {!! Form::label('enemy_forces', __('Enemy forces (-1 for unknown)')) !!}
        {!! Form::text('enemy_forces', isset($model) ? $model->enemy_forces : -1, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'enemy_forces'])
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