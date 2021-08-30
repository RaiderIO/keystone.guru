@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$expansion ?? null],
    'showAds' => false,
    'title' => $expansion ? __('views/admin.expansion.edit.title_edit') : __('views/admin.expansion.edit.title_new')
    ])
@section('header-title', $expansion ? __('views/admin.expansion.edit.header_edit') : __('views/admin.expansion.edit.header_new'))

@section('content')
    @isset($expansion)
        {{ Form::model($expansion, ['route' => ['admin.expansion.update', $expansion->id], 'method' => 'patch', 'files' => true]) }}
    @else
        {{ Form::open(['route' => 'admin.expansion.savenew', 'files' => true]) }}
    @endisset

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('name', __('views/admin.expansion.edit.name')) !!}
        {!! Form::text('name', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('shortname', __('views/admin.expansion.edit.shortname')) !!}
        {!! Form::text('shortname', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'shortname'])
    </div>

    <div class="form-group{{ $errors->has('icon') ? ' has-error' : '' }}">
        {!! Form::label('icon', __('views/admin.expansion.edit.icon')) !!}
        {!! Form::file('icon', ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'icon'])
    </div>

    @isset($expansion)
        <div class="form-group">
            {{ __('views/admin.expansion.edit.current_image') }}: <img src="{{ $expansion->iconfile->getUrl() }}"
                                          style="width: 32px; height: 32px;"/>
        </div>
    @endisset

    <div class="form-group{{ $errors->has('color') ? ' has-error' : '' }}">
        {!! Form::label('color', __('views/admin.expansion.edit.color')) !!}
        {!! Form::color('color', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'color'])
    </div>

    {!! Form::submit(isset($expansion) ?
        __('views/admin.expansion.edit.edit') :
        __('views/admin.expansion.edit.submit'), ['class' => 'btn btn-info']) !!}

    {!! Form::close() !!}
@endsection
