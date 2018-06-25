@extends('layouts.app')
@section('header-title')
    {{ $headerTitle }}
    <a href="{{ route('admin.npcs') }}" class="btn btn-info text-white pull-right" role="button">
        <i class="fa fa-backward"></i> {{ __('Npc list') }}
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
        {{ Form::model($model, ['route' => ['admin.npc.update', $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => 'admin.npc.savenew']) }}
    @endisset

<div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
    {!! Form::label('name', __('Name')) !!}
    {!! Form::text('name', null, ['class' => 'form-control']) !!}
    @include('common.forms.form-error', ['key' => 'name'])
</div>

<div class="form-group{{ $errors->has('classification') ? ' has-error' : '' }}">
    {!! Form::label('classification', __('Classification')) !!}
    {!! Form::select('classification', $classifications, null, ['class' => 'form-control']) !!}
    @include('common.forms.form-error', ['key' => 'classification'])
</div>

<div class="form-group{{ $errors->has('base_health') ? ' has-error' : '' }}">
    {!! Form::label('base_health', __('Base health')) !!}
    {!! Form::text('base_health', null, ['class' => 'form-control']) !!}
    @include('common.forms.form-error', ['key' => 'base_health'])
</div>

<div class="form-group{{ $errors->has('game_id') ? ' has-error' : '' }}">
    {!! Form::label('game_id', __('Game ID')) !!}
    {!! Form::text('game_id', null, ['class' => 'form-control']) !!}
    @include('common.forms.form-error', ['key' => 'game_id'])
</div>

{!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}

{!! Form::close() !!}
@endsection