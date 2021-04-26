@extends('layouts.sitepage', ['showAds' => false, 'title' => __('Throw an exception')])

@section('header-title', __('Throw an exception'))

@section('content')
    {{ Form::open(['route' => 'admin.tools.exception.select.submit']) }}
    <div class="form-group">
        {!! Form::label('exception', __('Select exception to throw')) !!}
        {{ Form::select('exception', [
        'TokenMismatchException' => 'TokenMismatchException',
        'InternalServerError' => 'InternalServerError'
        ], null, ['class' => 'form-control selectpicker']) }}
    </div>
    <div class="form-group">
        {!! Form::submit(__('Submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection