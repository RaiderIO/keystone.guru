<?php
/** @var $isProduction string */
/** @var $isMobile boolean */

$user = \Illuminate\Support\Facades\Auth::user();
// Show ads if not set
$showAds = $showAds ?? true;
// Any class to add to the root div
$rootClass = $rootClass ?? '';
// Page title
$title = $title ?? null;
$cookieConsent = $cookieConsent ?? true;
?>
@extends('layouts.app', ['showSpotlight' => false, 'showAds' => $showAds, 'title' => $title, 'showAds' => $showAds, 'cookieConsent' => $cookieConsent])

@section('app-content')

    @yield('content')

@endsection