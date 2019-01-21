<?php
/** @var \App\User $user */
$user = Auth::getUser();
?>

@extends('layouts.app', ['wide' => true, 'title' => __('Profile')])

@section('header-title', sprintf(__('%s\'s profile'), $user->name))

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            newPassword('#new_password');
        });
    </script>
@endsection

@section('content')

    <profilevue></profilevue>

    <div class="mt-4">
        <h3>{{ __('My routes') }}</h3>

        @include('common.dungeonroute.table', ['profile' => true])
    </div>
@endsection