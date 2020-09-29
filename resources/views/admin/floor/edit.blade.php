<?php
/** @var $dungeon \App\Models\Dungeon */
/* @var $model \App\Models\Floor */
$connectedFloors = $model->dungeon->floors->except($model->id);
?>
@extends('layouts.app', ['showAds' => false, 'title' => $headerTitle])
@section('header-title')
    {{ $headerTitle }}
@endsection
@section('header-addition')
    <a href="{{ route('admin.dungeon.edit', ['dungeon' => $dungeon]) }}" class="btn btn-info text-white pull-right"
       role="button">
        <i class="fas fa-backward"></i> {{ sprintf(__('Edit %s'), $dungeon->name) }}
    </a>
@endsection
<?php
/**
 */
?>

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['admin.floor.update', 'dungeon' => $dungeon->id, 'floor' => $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => ['admin.floor.savenew', 'dungeon' => $model->dungeon->id]]) }}
    @endisset

    <div class="form-group{{ $errors->has('index') ? ' has-error' : '' }}">
        {!! Form::label('index', __('Index'), ['class' => 'font-weight-bold']) !!}
        <span class="form-required">*</span>
        {!! Form::text('index', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'index'])
    </div>

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('name', __('Floor name'), ['class' => 'font-weight-bold']) !!}
        <span class="form-required">*</span>
        {!! Form::text('name', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    @if($connectedFloors->isNotEmpty())
        <div class="form-group">
            {!! Form::label('connectedfloors[]', __('Connected floors'), ['class' => 'font-weight-bold']) !!}
            <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                __('A connected floor is any other floor that we may reach from this floor')
                 }}"></i>
            {!! Form::select('connectedfloors[]', $connectedFloors->pluck('name', 'id'), $model->connectedFloors()->pluck('id')->all(),
                ['multiple' => 'multiple', 'class' => 'form-control selectpicker']) !!}
        </div>
    @endif

    {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}

    {!! Form::close() !!}
@endsection
