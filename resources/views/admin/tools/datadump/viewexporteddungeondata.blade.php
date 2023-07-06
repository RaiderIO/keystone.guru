@extends('layouts.sitepage', ['showAds' => false, 'title' => __('views/admin.tools.datadump.viewexporteddungeondata.title')])

@section('header-title', __('views/admin.tools.datadump.viewexporteddungeondata.header'))

@section('content')
    {{ __('views/admin.tools.datadump.viewexporteddungeondata.content') }}
@endsection