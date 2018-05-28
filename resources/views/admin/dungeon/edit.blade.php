@extends('layouts.app')
@section('header-title', $headerTitle)

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['admin.dungeon.update', $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => 'admin.dungeon.savenew', 'files' => true]) }}
    @endisset

<div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
    {!! Form::label('name', __('Dungeon name')) !!}
    {!! Form::text('name', null, ['class' => 'form-control']) !!}
    @include('common.forms.form-error', ['key' => 'name'])
</div>

<div class="form-group{{ $errors->has('key') ? ' has-error' : '' }}">
    {!! Form::label('key', __('Key')) !!}
    {!! Form::text('key', null, ['class' => 'form-control']) !!}
    @include('common.forms.form-error', ['key' => 'key'])
</div>

{!! Form::submit('Submit', ['class' => 'btn btn-info']) !!}

{!! Form::close() !!}
@endsection
