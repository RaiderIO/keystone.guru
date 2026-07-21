@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.combatlog.regenerate.title')])

@section('header-title', __('view_admin.tools.combatlog.regenerate.header'))

@section('content')
    {{ html()->form('POST', route('admin.tools.combatlog.regenerate.submit'))->open() }}
    <div class="mb-3">
        @include('common.dungeon.select', ['activeOnly' => false])
    </div>
    <div class="mb-3">
        {{ html()->input('submit')->value(__('view_admin.tools.combatlog.regenerate.submit'))->class('btn btn-primary col-md-auto') }}
    </div>
    {{ html()->form()->close() }}
@endsection
