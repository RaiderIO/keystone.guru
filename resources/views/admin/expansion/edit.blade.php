@extends('layouts.app', ['showAds' => false, 'title' => __('Edit expansion')])
@section('header-title', __('View expansions'))
@section('header-addition')
    <a href="{{ route('admin.expansions') }}" class="btn btn-info text-white float-right" role="button">
        <i class="fas fa-backward"></i> {{ __('Expansion list') }}
    </a>
@endsection

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['admin.expansion.update', $model->id], 'method' => 'patch', 'files' => true]) }}
    @else
        {{ Form::open(['route' => 'admin.expansion.savenew', 'files' => true]) }}
    @endisset

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('name', __('Name')) !!}
        {!! Form::text('name', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('shortname', __('Shortname')) !!}
        {!! Form::text('shortname', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'shortname'])
    </div>

    <div class="form-group{{ $errors->has('icon') ? ' has-error' : '' }}">
        {!! Form::label('icon', __('Icon')) !!}
        {!! Form::file('icon', ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'icon'])
    </div>

    @isset($model)
    <div class="form-group">
        {{__('Current image:')}} <img src="{{ Image::url($model->iconfile->getUrl(), 32, 32) }}"/>
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
