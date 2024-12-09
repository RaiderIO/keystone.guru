<?php

use App\Models\Team;

/**
 * @var Team $team
 */

$title  = isset($team) ? sprintf(__('view_team.invite.title'), $team->name) : __('view_team.invite.invalid_team');
$header = isset($team) ? sprintf(__('view_team.invite.header'), $team->name) : __('view_team.invite.invalid_team');
// Checks if you're already a member or not
$member ??= false;
?>
@extends('layouts.sitepage', ['breadcrumbsParams' => [$team], 'showAds' => false, 'title' => $title])
@section('header-title', $header)

@section('linkpreview')
    @include('common.general.linkpreview', [
        'title' => sprintf(__('view_team.invite.linkpreview_title'), $team->name),
        'description' => sprintf(__('view_team.invite.linkpreview_description'), $team->name),
        'image' => $team->iconfile?->getURL()
    ])
@endsection

@section('content')
    <div class="container text-center">
        @isset($team)
            @isset($team->iconfile)
                <p>
                    <img src="{{ $team->iconfile->getURL() }}" style="max-width: 256px; max-height: 256px;"
                         alt="{{ __('view_team.invite.logo_image_alt') }}"/>
                </p>
            @endisset
            <p>
                @if( $member )
                    {{ sprintf(__('view_team.invite.already_a_member'), $team->name) }}
                @else
                    {{ sprintf(__('view_team.invite.invited_to_join'), $team->name) }}
                    @auth
                        {{ __('view_team.invite.accept_the_invitation') }}
                    @else
                        {{ __('view_team.invite.login_or_register_to_accept') }}
                    @endauth
                @endif
            </p>
            <div class="row">
                <div class="col">
                    @if( $member )
                        <a href="{{ route('team.edit', ['team' => $team]) }}" class="btn btn-primary col-lg-auto">
                            <i class="fas fa-backward"></i> {{ __('view_team.invite.return_to_team') }}
                        </a>
                    @else
                        @auth
                            <a href="{{ route('team.invite.accept', ['invitecode' => $team->invite_code ]) }}"
                               class="btn btn-primary col-lg-auto">
                                <i class="fas fa-user-plus"></i> {{ __('view_team.invite.accept_invitation') }}
                            </a>
                        @else
                            <button class="btn btn-primary col-lg-auto" data-toggle="modal" data-target="#login_modal">
                                {{ __('view_team.invite.login') }}
                            </button>
                            <button class="btn btn-primary col-lg-auto" data-toggle="modal"
                                    data-target="#register_modal">
                                {{ __('view_team.invite.register') }}
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
                {{ __('view_team.invite.invite_not_found') }}
            </p>
            <button class="btn btn-primary col-lg-auto">
                <i class="fas fa-home"></i> {{ __('view_team.invite.back_to_homepage') }}
            </button>
        @endisset
    </div>
@endsection
