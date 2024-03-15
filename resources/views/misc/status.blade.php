@extends('layouts.sitepage', ['cookieConsent' => false, 'showAds' => false, 'analytics' => false, 'title' => __('view_misc.status.title')])

@section('header-title', __('view_misc.status.header'))

@section('content')

    {{ __('view_misc.status.description') }}

@endsection
