@extends('layouts.sitepage', ['title' => __('views/auth.login.title'), 'showAds' => false])

@section('content')
    @include('common.forms.login')
@endsection
