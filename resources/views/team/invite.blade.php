<?php
$title = isset($team) ? sprintf(__('Invitation to join team %s'), $team->name) : __('Invalid team');
// Checks if you're already a member or not
$member = isset($member) ? $member : false;
?>
@extends('layouts.sitepage', ['breadcrumbsParams' => [$team], 'showAds' => false, 'title' => $title])
@section('header-title', $title)

@section('content')
    <div class="container text-center">
        @isset($team)
            @isset($team->iconfile)
                <p>
                    <img src="{{ $team->iconfile->getURL() }}" style="max-width: 256px; max-height: 256px;"
                         alt="{{ __('Team logo') }}"/>
                </p>
            @endisset
            <p>
                @if( $member )
                    {{ sprintf(__('You are already a member of team %s!'), $team->name) }}
                @else
                    {{ sprintf(__('You have been invited to join team %s.'), $team->name) }}
                    @auth
                        {{ __('Accept the invitation to join the team!') }}
                    @else
                        {{ __('Login or register on Keystone.guru to join the team, it\'s free!') }}
                    @endauth
                @endif
            </p>
            <div class="row">
                <div class="col">
                    @if( $member )
                        <a href="{{ route('team.edit', ['team' => $team]) }}" class="btn btn-primary col-lg-auto">
                            <i class="fas fa-backward"></i> {{ __('Return to team') }}
                        </a>
                    @else
                        @auth
                            <a href="{{ route('team.invite.accept', ['invitecode' => $team->invite_code ]) }}"
                               class="btn btn-primary col-lg-auto">
                                <i class="fas fa-user-plus"></i> {{ __('Accept invitation') }}
                            </a>
                        @else
                            <button class="btn btn-primary col-lg-auto" data-toggle="modal" data-target="#login_modal">
                                {{ __('Login') }}
                            </button>
                            <button class="btn btn-primary col-lg-auto" data-toggle="modal"
                                    data-target="#register_modal">
                                {{ __('Register now!') }}
                            </button>
                        @endauth
                    @endif
                </div>
            </div>
        @else
            <h1 class="text-primary">
                <i class="fas fa-ban"></i>
            </h1>
            <p>
                {{ __('This team could not be found. Perhaps the invite link has been changed or the team has been deleted.') }}
            </p>
            <button class="btn btn-primary col-lg-auto">
                <i class="fas fa-home"></i> {{ __('Back to the home page') }}
            </button>
        @endisset
    </div>
@endsection
