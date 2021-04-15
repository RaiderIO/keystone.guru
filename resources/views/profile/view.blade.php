<?php
/** @var $user \App\User */
$title = sprintf(__('%s\'s routes'), $user->name);
?>
@extends('layouts.sitepage', ['wide' => true, 'title' => $title])

@include('common.general.inline', ['path' => 'profile/view', 'options' => [
    'dependencies' => ['dungeonroute/table'],
    'user' => $user
]])

@section('header-title')
    {{ $title }}
@endsection

@section('content')
    @include('common.general.messages')

    @include('common.dungeonroute.table', ['view' => 'userprofile'])
@endsection