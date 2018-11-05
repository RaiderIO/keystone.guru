@extends('layouts.app', ['title' => __('Register')])

@section('header-title', 'Register')
@section('content')
    @include('common.forms.register')
@endsection
