@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.mdt.dungeonmappinghash.title')])

@section('header-title', __('view_admin.tools.mdt.dungeonmappinghash.header'))

@section('content')
    {{ html()->form('POST', route('admin.tools.mdt.dungeonmappinghash.submit'))->open() }}
    @include('common.dungeon.select', ['activeOnly' => false, 'showAll' => false])
    <div class="form-group">
        {{ html()->input('submit')->value(__('view_admin.tools.mdt.dungeonmappinghash.submit'))->class('btn btn-primary col-md-auto') }}
        <div class="col-md">

        </div>
    </div>
    {{ html()->form()->close() }}
@endsection
