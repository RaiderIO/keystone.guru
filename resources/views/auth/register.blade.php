@extends('layouts.app', ['title' => __('Register'), 'showAds' => false])

@section('header-title', 'Register')
@section('content')
    @include('common.forms.register')
@endsection
