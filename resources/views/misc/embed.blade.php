@extends('layouts.app', ['showLegalModal' => false, 'title' => __('Embed a route')])

@section('header-title', __('Keystone.guru embed test on webpage'))

@section('content')
    <iframe src="{{ route('dungeonroute.embed', ['dungeonroute' => $model]) }}" style="width: 800px; height: 600px; border: none;"></iframe>
@endsection