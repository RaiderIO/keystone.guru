@extends('layouts.sitepage', ['title' => __('views/auth.register.title'), 'showAds' => false])

@section('header-title', __('views/auth.register.header'))
@section('content')
    @include('common.forms.register')
@endsection
