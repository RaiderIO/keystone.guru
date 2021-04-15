@extends('layouts.sitepage', ['breadcrumbsParams' => [$expansion ?? null], 'showAds' => false, 'title' => $headerTitle])
@section('header-title', $headerTitle)

@section('content')
    @isset($expansion)
        {{ Form::model($expansion, ['route' => ['admin.expansion.update', $expansion->id], 'method' => 'patch', 'files' => true]) }}
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

    @isset($expansion)
        <div class="form-group">
            {{__('Current image:')}} <img src="{{ $expansion->iconfile->getUrl() }}"
                                          style="width: 32px; height: 32px;"/>
        </div>
    @endisset

    <div class="form-group{{ $errors->has('color') ? ' has-error' : '' }}">
        {!! Form::label('color', __('Color')) !!}
        {!! Form::color('color', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'color'])
    </div>

    {!! Form::submit(isset($expansion) ? __('Edit') : __('Submit'), ['class' => 'btn btn-info']) !!}

    {!! Form::close() !!}
@endsection
