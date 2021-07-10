@extends('layouts.sitepage', ['title' => __('Login'), 'showAds' => false])

@section('content')
    @include('common.forms.login')
@endsection
