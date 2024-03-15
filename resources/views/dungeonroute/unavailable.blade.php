@extends('layouts.sitepage', ['title' => __('view_dungeonroute.unavailable.title')])
@section('header-title', $headerTitle)

@section('content')
    <div class="text-center">
        {{ __('view_dungeonroute.unavailable.unavailable_description') }}
    </div>
@endsection

