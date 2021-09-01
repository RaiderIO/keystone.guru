@extends('layouts.sitepage', ['showAds' => false, 'title' => __('views/admin.tools.datadump.viewexportedrelease.title')])

@section('header-title', __('views/admin.tools.datadump.viewexportedrelease.header'))

@section('content')
    {{ __('views/admin.tools.datadump.viewexportedrelease.content') }}
@endsection