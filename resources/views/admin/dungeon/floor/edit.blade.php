@extends('layouts.app')
@section('header-title')
    {{ $headerTitle }}
    <a href="{{ route('admin.dungeon.edit', ['id' => $dungeon->id]) }}" class="btn btn-info text-white pull-right" role="button">{{ __('Back to dungeon') }}</a>
@endsection
<?php
/**
 * @var $model \App\Models\Floor
 * @var $dungeon \App\Models\Dungeon
 */
?>

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['admin.dungeon.floor.update', $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => ['admin.dungeon.floor.savenew', $dungeon->id], 'files' => true]) }}
    @endisset

<div class="form-group">
    {!! Form::label('dungeon', __('Dungeon')) !!}
    {!! Form::select('dungeon', [$dungeon->id => $dungeon->name], null, ['class' => 'form-control', 'readonly' => 'readonly']) !!}
</div>

<div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
    {!! Form::label('name', __('Floor name')) !!}
    {!! Form::text('name', null, ['class' => 'form-control']) !!}
    @include('common.forms.form-error', ['key' => 'name'])
</div>

{!! Form::submit('Submit', ['class' => 'btn btn-info']) !!}

{!! Form::close() !!}
@endsection
