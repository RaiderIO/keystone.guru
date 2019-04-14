@extends('layouts.app', ['showAds' => false, 'title' => __('Edit team')])
@section('header-title', __('Edit team'))
@section('header-addition')
    <a href="{{ route('team.list') }}" class="btn btn-info text-white float-right" role="button">
        <i class="fas fa-backward"></i> {{ __('Team list') }}
    </a>
@endsection

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['team.update', $model->id], 'method' => 'patch', 'files' => true]) }}
    @else
        {{ Form::open(['route' => 'team.savenew', 'files' => true]) }}
    @endisset

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('name', __('Name')) !!}
        {!! Form::text('name', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('description', __('Description')) !!}
        {!! Form::text('description', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'description'])
    </div>

    <div class="form-group{{ $errors->has('logo') ? ' has-error' : '' }}">
        {!! Form::label('logo', __('Logo')) !!}
        {!! Form::file('logo', ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'logo'])
    </div>

    @isset($model)
        <div class="form-group">
            {{__('Current logo:')}} <img src="{{ Image::url($model->iconfile->getUrl(), 32, 32) }}"
                                         alt="{{ __('Team logo') }}"/>
        </div>
    @endisset

    {!! Form::submit(isset($model) ? __('Edit') : __('Submit'), ['class' => 'btn btn-info']) !!}

    {!! Form::close() !!}
@endsection
