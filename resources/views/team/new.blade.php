<?php
// Checks if you're already a member or not
$member = $member ?? false;
?>
@extends('layouts.sitepage', ['title' => __('views/team.new.title')])
@section('header-title', __('views/team.new.header'))

@section('content')
    <div class="container">
        @include('common.team.details')
    </div>
@endsection
