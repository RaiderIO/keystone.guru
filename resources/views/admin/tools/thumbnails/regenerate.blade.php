@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.thumbnails.regenerate.title')])

@section('header-title', __('view_admin.tools.thumbnails.regenerate.header'))

@section('content')
    {{ html()->form('POST', route('admin.tools.thumbnails.regenerate.submit'))->open() }}
    <div class="mb-3">
        @include('common.dungeon.select', ['activeOnly' => false])
    </div>
    <div class="mb-3">
        <div class="form-check">
            {{ html()->checkbox('only_missing', false, 1)->class('form-check-input') }}
            {{ html()->label(__('view_admin.tools.thumbnails.regenerate.only_missing'), 'only_missing')->class('form-check-label') }}
        </div>
    </div>
    <div class="mb-3">
        <div class="form-check">
            {{ html()->checkbox('force', false, 1)->class('form-check-input') }}
            {{ html()->label(__('view_admin.tools.thumbnails.regenerate.force'), 'force')->class('form-check-label') }}
        </div>
    </div>
    <div class="mb-3">
        {{ html()->input('submit')->value(__('view_admin.tools.thumbnails.regenerate.submit'))->class('btn btn-primary col-md-auto') }}
        <div class="col-md">

        </div>
    </div>
    {{ html()->form()->close() }}
@endsection
