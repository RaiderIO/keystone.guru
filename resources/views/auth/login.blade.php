@extends('layouts.app', ['title' => __('Login')])

@section('header-title', 'Login')
@section('content')
    @include('common.forms.login')
@endsection
