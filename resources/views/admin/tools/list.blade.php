@extends('layouts.app', ['showAds' => false, 'title' => __('Admin tools')])

@section('header-title', __('Admin tools'))

@section('content')
    <h3>{{ __('Tools') }}</h3>
    <div class="form-group">
        <a href="{{ route('admin.tools.mdt.string.view') }}">{{ __('View MDT String contents') }}</a>
    </div>
    <div class="form-group">
        <a href="{{ route('admin.tools.mdt.string.viewasdungeonroute') }}">{{ __('View MDT String as DungeonRoute') }}</a>
    </div>
    <div class="form-group">
        <a href="{{ route('admin.tools.mdt.diff') }}">{{ __('View MDT Diff') }}</a>
    </div>
    <h3>{{ __('Actions') }}</h3>
    <div class="form-group">
        <a class="btn btn-primary" href="{{ route('admin.tools.datadump.exportdungeondata') }}">{{ __('Export dungeon data') }}</a>
    </div>
    <div class="form-group">
        <a class="btn btn-primary" href="{{ route('admin.tools.datadump.exportreleases') }}">{{ __('Export releases') }}</a>
    </div>
@endsection