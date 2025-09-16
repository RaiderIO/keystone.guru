@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.thumbnails.regenerate.title')])

@section('header-title', __('view_admin.tools.thumbnails.regenerate.header'))

@section('content')
    {{ html()->form('POST', route('admin.tools.thumbnails.regenerate.submit'))->open() }}
    <div class="form-group">
        @include('common.dungeon.select', ['activeOnly' => false])
    </div>
    <div class="form-group">
        {{ html()->label(__('view_admin.tools.thumbnails.regenerate.only_missing'), 'truesight') }}
        {{ html()->checkbox('only_missing', false, 1)->class('form-control left_checkbox') }}
    </div>
    <div class="form-group">
        {{ html()->input('submit')->value(__('view_admin.tools.thumbnails.regenerate.submit'))->class('btn btn-primary col-md-auto') }}
        <div class="col-md">

        </div>
    </div>
    {{ html()->form()->close() }}
@endsection
