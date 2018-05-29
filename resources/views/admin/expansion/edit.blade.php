@extends('layouts.app')
@section('header-title', $headerTitle)

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['admin.expansion.update', $model->id], 'method' => 'patch', 'files' => true]) }}
    @else
        {{ Form::open(['route' => 'admin.expansion.savenew', 'files' => true]) }}
    @endisset

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

    @isset($model)
    <div class="form-group">
        {{__('Current image:')}} <img src="{{ Image::url($model->icon->getUrl(), 32, 32) }}"/>
    </div>
    @endisset

    <div class="form-group{{ $errors->has('color') ? ' has-error' : '' }}">
        {!! Form::label('color', __('Color')) !!}
        {!! Form::color('color', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'color'])
    </div>

    {!! Form::submit(isset($model) ? __('Edit') : __('Submit'), ['class' => 'btn btn-info']) !!}

    {!! Form::close() !!}
@endsection
