@extends('layouts.sitepage', ['showAds' => false, 'title' => __('views/admin.tools.exception.select.title')])

@section('header-title', __('views/admin.tools.exception.select.header'))

@section('content')
    {{ Form::open(['route' => 'admin.tools.exception.select.submit']) }}
    <div class="form-group">
        {!! Form::label('exception', __('views/admin.tools.exception.select.select_exception_to_throw')) !!}
        {{ Form::select('exception', [
        'TokenMismatchException' => 'TokenMismatchException',
        'InternalServerError' => 'InternalServerError'
        ], null, ['class' => 'form-control selectpicker']) }}
    </div>
    <div class="form-group">
        {!! Form::submit(__('views/admin.tools.exception.select.submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection