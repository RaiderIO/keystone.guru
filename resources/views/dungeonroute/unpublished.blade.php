@extends('layouts.app', ['title' => __('Unpublished route')])
@section('header-title', $headerTitle)

@section('content')
    <div class="text-center">
        {{ __('This route is unpublished. Ask the author of the route to publish the route before you can view it.') }}
    </div>
@endsection

