<?php
/**
 * @var string|null $messageBanner
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.messagebanner.set.title')])

@section('header-title', __('view_admin.tools.messagebanner.set.header'))

@section('content')
    {{ html()->form('POST', route('admin.tools.messagebanner.set.submit'))->open() }}
    <div class="form-group">
        {{ html()->label(__('view_admin.tools.messagebanner.set.message'), 'message') }}
        {{ html()->textarea('message', $messageBanner ?? '')->class('form-control') }}
    </div>
    <div class="form-group">
        {{ html()->input('submit')->value(__('view_admin.tools.messagebanner.set.submit'))->class('btn btn-primary col-md-auto') }}
        <div class="col-md">

        </div>
    </div>
    {{ html()->form()->close() }}
@endsection
