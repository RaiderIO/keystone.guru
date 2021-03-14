<?php
/** @var $isProduction string */
/** @var $isMobile boolean */

$user = \Illuminate\Support\Facades\Auth::user();
// Show ads if not set
$showAds = isset($showAds) ? $showAds : true;
// Any class to add to the root div
$rootClass = isset($rootClass) ? $rootClass : '';
// Page title
$title = isset($title) ? $title : null;
?>
@extends('layouts.app', ['showAds' => $showAds, 'title' => $title, 'showAds' => $showAds])

@section('app-content')

    @yield('content')

@endsection