@extends('layouts.app')
@section('header-title', $headerTitle)

@section('content')
    {{ __('This route is unpublished. Ask the author of the route to publish the route before you can view it.') }}
@endsection

