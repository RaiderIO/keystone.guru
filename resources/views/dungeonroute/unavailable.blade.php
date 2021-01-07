@extends('layouts.app', ['title' => __('Unpublished route')])
@section('header-title', $headerTitle)

@section('content')
    <div class="text-center">
        {{ __('You are not authorized to view this route. Ask the author of the route to change the route\'s Sharing settings so that you can view it.') }}
    </div>
@endsection

