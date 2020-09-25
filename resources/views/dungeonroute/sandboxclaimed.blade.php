@extends('layouts.app', ['title' => __('Route already claimed')])
@section('header-title', __('Route already claimed'))

@section('content')
    <div class="mt-2 text-center">
        {{ __('This route has already been claimed by someone (or you used the back button in your browser to navigate here).') }}
    </div>
@endsection

