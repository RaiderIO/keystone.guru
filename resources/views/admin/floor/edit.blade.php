@extends('layouts.app', ['wide' => isset($model)])
@section('header-title')
    {{ $headerTitle }}
    <a href="{{ route('admin.dungeon.edit', ['id' => $dungeon->id]) }}" class="btn btn-info text-white pull-right"
       role="button">
        <i class="fa fa-backward"></i> {{ __('Back to dungeon') }}
    </a>
@endsection
<?php
/**
 * @var $model \App\Models\Floor
 * @var $dungeon \App\Models\Dungeon
 * @var $floors \Illuminate\Support\Collection
 */
?>

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['admin.floor.update', 'id' => $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => ['admin.floor.savenew', 'dungeon' => $dungeon->id]]) }}
    @endisset

    <div class="container">
        <div class="form-group">
            {!! Form::label('dungeon', __('Dungeon')) !!}
            {!! Form::select('dungeon', [$dungeon->id => $dungeon->name], null, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
        </div>

        <div class="form-group{{ $errors->has('index') ? ' has-error' : '' }}">
            {!! Form::label('index', __('Index')) !!}
            {!! Form::text('index', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'index'])
        </div>

        <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
            {!! Form::label('name', __('Floor name')) !!}
            {!! Form::text('name', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'name'])
        </div>

        <div class="form-group">
            {!! Form::label('connectedfloors[]', __('Connected floors')) !!}
            {!! Form::select('connectedfloors[]', $floors->pluck('name', 'id'), isset($model) ? $model->connectedFloors()->pluck('id')->all() : null,
                ['multiple' => 'multiple', 'class' => 'form-control']) !!}
        </div>

        {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}

        {!! Form::close() !!}
    </div>

    @isset($model)
        <h3>Enemy placement</h3>
        @include('common.maps.map', ['admin' => true, 'dungeons' => new \Illuminate\Support\Collection([$dungeon]), 'npcs' => $npcs])
    @endisset

@endsection
