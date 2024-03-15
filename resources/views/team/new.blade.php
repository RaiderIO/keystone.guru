<?php
// Checks if you're already a member or not
$member ??= false;
?>
@extends('layouts.sitepage', ['title' => __('view_team.new.title')])
@section('header-title', __('view_team.new.header'))

@section('content')
    <div class="container">
        @include('common.team.details')
    </div>
@endsection
