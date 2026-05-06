<?php
/**
 * @var array $exceptions
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.exception.select.title')])

@section('header-title', __('view_admin.tools.exception.select.header'))

@section('content')
    {{ html()->form('POST', route('admin.tools.exception.select.submit'))->open() }}
    <div class="form-group">
        {{ html()->label(__('view_admin.tools.exception.select.select_exception_to_throw'), 'exception') }}
        {{ html()->select('exception', $exceptions)->class('form-control selectpicker') }}
    </div>
    <div class="form-group">
        {{ html()->input('submit')->value(__('view_admin.tools.exception.select.submit'))->class('btn btn-primary col-md-auto') }}
        <div class="col-md">

        </div>
    </div>
    {{ html()->form()->close() }}
@endsection
