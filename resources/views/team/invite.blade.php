<?php
/** @var \App\Models\Team $team */

$title  = isset($team) ? sprintf(__('views/team.invite.title'), $team->name) : __('views/team.invite.invalid_team');
$header = isset($team) ? sprintf(__('views/team.invite.header'), $team->name) : __('views/team.invite.invalid_team');
// Checks if you're already a member or not
$member = $member ?? false;
?>
@extends('layouts.sitepage', ['breadcrumbsParams' => [$team], 'showAds' => false, 'title' => $title])
@section('header-title', $header)

@section('linkpreview')
    @include('common.general.linkpreview', [
        'title' => sprintf(__('views/team.invite.linkpreview_title'), $team->name),
        'description' => sprintf(__('views/team.invite.linkpreview_description'), $team->name),
        'image' => optional($team->iconfile)->getURL()
    ])
@endsection

@section('content')
    <div class="container text-center">
        @isset($team)
            @isset($team->iconfile)
                <p>
                    <img src="{{ $team->iconfile->getURL() }}" style="max-width: 256px; max-height: 256px;"
                         alt="{{ __('views/team.invite.logo_image_alt') }}"/>
                </p>
            @endisset
            <p>
                @if( $member )
                    {{ sprintf(__('views/team.invite.already_a_member'), $team->name) }}
                @else
                    {{ sprintf(__('views/team.invite.invited_to_join'), $team->name) }}
                    @auth
                        {{ __('views/team.invite.accept_the_invitation') }}
                    @else
                        {{ __('views/team.invite.login_or_register_to_accept') }}
                    @endauth
                @endif
            </p>
            <div class="row">
                <div class="col">
                    @if( $member )
                        <a href="{{ route('team.edit', ['team' => $team]) }}" class="btn btn-primary col-lg-auto">
                            <i class="fas fa-backward"></i> {{ __('views/team.invite.return_to_team') }}
                        </a>
                    @else
                        @auth
                            <a href="{{ route('team.invite.accept', ['invitecode' => $team->invite_code ]) }}"
                               class="btn btn-primary col-lg-auto">
                                <i class="fas fa-user-plus"></i> {{ __('views/team.invite.accept_invitation') }}
                            </a>
                        @else
                            <button class="btn btn-primary col-lg-auto" data-toggle="modal" data-target="#login_modal">
                                {{ __('views/team.invite.login') }}
                            </button>
                            <button class="btn btn-primary col-lg-auto" data-toggle="modal"
                                    data-target="#register_modal">
                                {{ __('views/team.invite.register') }}
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
                {{ __('views/team.invite.invite_not_found') }}
            </p>
            <button class="btn btn-primary col-lg-auto">
                <i class="fas fa-home"></i> {{ __('views/team.invite.back_to_homepage') }}
            </button>
        @endisset
    </div>
@endsection
