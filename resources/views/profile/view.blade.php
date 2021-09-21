<?php
/** @var $user \App\User */
$title  = sprintf(__('views/profile.view.title'), $user->name);
$header = sprintf(__('views/profile.view.header'), $user->name);
?>
@extends('layouts.sitepage', ['wide' => true, 'title' => $title])

@include('common.general.inline', ['path' => 'profile/view', 'options' => [
    'dependencies' => ['dungeonroute/table'],
    'user' => $user
]])

@section('header-title')
    {{ $header }}
@endsection

@section('content')
    @include('common.general.messages')

    @include('common.dungeonroute.table', ['view' => 'userprofile'])
@endsection
