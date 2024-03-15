<?php
/** @var $user \App\Models\User */
$title  = sprintf(__('view_profile.view.title'), $user->name);
$header = sprintf(__('view_profile.view.header'), $user->name);
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
