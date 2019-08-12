@extends('layouts.app', ['title' => __('Login'), 'showAds' => false])

@section('header-title', 'Login')
@section('content')
    @include('common.forms.login')
@endsection
