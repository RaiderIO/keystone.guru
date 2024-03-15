@extends('layouts.sitepage', ['title' => __('view_auth.register.title'), 'showAds' => false])

@section('header-title', __('view_auth.register.header'))
@section('content')
    @include('common.forms.register')
@endsection
