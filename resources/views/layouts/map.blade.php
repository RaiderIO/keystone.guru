<?php
/**
 * @var $isProduction string
 * @var $isMobile boolean
 */
$user = \Illuminate\Support\Facades\Auth::user();
// Show ads if not set
$showAds ??= true;
// Any class to add to the root div
$rootClass ??= '';
// Page title
$title         ??= null;
$cookieConsent ??= true;
?>
@extends('layouts.app', ['showSpotlight' => false, 'showAds' => $showAds, 'title' => $title, 'cookieConsent' => $cookieConsent])

@section('app-content')

    @yield('content')

@endsection
