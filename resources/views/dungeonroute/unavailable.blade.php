@extends('layouts.sitepage', ['title' => __('views/dungeonroute.unavailable.title')])
@section('header-title', $headerTitle)

@section('content')
    <div class="text-center">
        {{ __('views/dungeonroute.unavailable.unavailable_description') }}
    </div>
@endsection

