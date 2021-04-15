@extends('layouts.sitepage', ['title' => __('Register'), 'showAds' => false])

@section('header-title', 'Register')
@section('content')
    @include('common.forms.register')
@endsection
