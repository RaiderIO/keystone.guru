@extends('layouts.sitepage', ['cookieConsent' => false, 'showAds' => false, 'analytics' => false, 'title' => __('views/misc.status.title')])

@section('header-title', __('views/misc.status.header'))

@section('content')

{{ __('views/misc.status.description') }}

@endsection