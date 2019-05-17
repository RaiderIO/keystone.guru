<?php
$title = __('New team');
// Checks if you're already a member or not
$member = isset($member) ? $member : false;
?>
@extends('layouts.app', ['title' => $title])
@section('header-title', $title)

@section('content')
    <div class="container">
        @include('team.details')
    </div>
@endsection
