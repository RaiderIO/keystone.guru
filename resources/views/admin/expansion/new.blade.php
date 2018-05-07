@extends('layouts.app')

@section('header-title', 'Create expansion')

@section('content')
{!! Form::open(['route' => 'admin.expansion.store']) !!}

<div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
    {!! Form::label('name', __('Expansion name')) !!}
    {!! Form::text('name', null, ['class' => 'form-control']) !!}
    @include('common.forms.form-error', ['key' => 'name'])
</div>

<div class="form-group{{ $errors->has('icon') ? ' has-error' : '' }}">
    {!! Form::label('icon', __('Icon')) !!}
    {!! Form::file('icon', ['class' => 'form-control']) !!}
    @include('common.forms.form-error', ['key' => 'icon'])
</div>

<div class="form-group{{ $errors->has('color') ? ' has-error' : '' }}">
    {!! Form::label('color', __('Color')) !!}
    {!! Form::color('color', null, ['class' => 'form-control']) !!}
    @include('common.forms.form-error', ['key' => 'color'])
</div>

{!! Form::submit('Submit', ['class' => 'btn btn-info']) !!}

{!! Form::close() !!}
@endsection
