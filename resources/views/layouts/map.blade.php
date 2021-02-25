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
@extends('layouts.app', ['title' => $title, 'showAds' => $showAds])

@section('app-content')

    @include('common.maps.header')

    @yield('content')

@endsection