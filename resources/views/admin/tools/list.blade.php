@extends('layouts.app', ['showAds' => false, 'title' => __('Admin tools')])

@section('header-title', __('Admin tools'))

@section('content')
    <div class="form-group">
        <a href="{{ route('admin.tools.mdt.string.view') }}">{{ __('View MDT String contents') }}</a>
    </div>
    <div class="form-group">
        <a href="{{ route('admin.tools.mdt.diff') }}">{{ __('View MDT Diff') }}</a>
    </div>
    <div class="form-group">
        <a href="{{ route('admin.tools.datadump.exportdungeondata') }}">{{ __('Export dungeon data') }}</a>
    </div>
@endsection