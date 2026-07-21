@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.mdt.dungeonroute.title')])

@section('header-title', __('view_admin.tools.mdt.dungeonroute.header'))

@section('content')
    {{ html()->form('POST', route('admin.tools.mdt.dungeonroute.viewasstring.submit'))->open() }}
    <div class="mb-3">
        {{ html()->label(__('view_admin.tools.mdt.dungeonroute.public_key'), 'public_key') }}
        {{ html()->text('public_key', '')->class('form-control')->data('simplebar', '') }}
    </div>
    <div class="mb-3">
        {{ html()->input('submit')->value(__('view_admin.tools.mdt.dungeonroute.submit'))->class('btn btn-primary col-md-auto') }}
        <div class="col-md">

        </div>
    </div>
    {{ html()->form()->close() }}
@endsection
