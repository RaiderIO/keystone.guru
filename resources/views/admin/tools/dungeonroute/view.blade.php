@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.dungeonroute.view.title')])

@section('header-title', __('view_admin.tools.dungeonroute.view.header'))

@section('content')
    {{ html()->form('POST', route('admin.tools.dungeonroute.view.submit'))->open() }}
    <div class="form-group">
        {{ html()->label(__('view_admin.tools.dungeonroute.view.public_key'), 'public_key') }}
        {{ html()->text('public_key', '')->class('form-control')->data('simplebar', '') }}
    </div>
    <div class="form-group">
        {{ html()->input('submit')->value(__('view_admin.tools.dungeonroute.view.submit'))->class('btn btn-primary col-md-auto') }}
        <div class="col-md">

        </div>
    </div>
    {{ html()->form()->close() }}
@endsection
