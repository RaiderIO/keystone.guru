@extends('layouts.sitepage', ['wide' => true, 'title' => __('My routes')])

@section('header-title')
    {{ __('My routes') }}
@endsection

@section('content')
    {{ sprintf('This is %s profile', $user->name) }}
@endsection