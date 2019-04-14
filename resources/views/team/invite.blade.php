<?php
$title = isset($team) ? sprintf(__('Invitation to join team %s'), $team->name) : __('Invalid team');
?>
@extends('layouts.app', ['showAds' => false, 'title' => $title])
@section('header-title', $title)
@section('header-addition')
    <a href="{{ route('team.list') }}" class="btn btn-info text-white float-right" role="button">
        <i class="fas fa-backward"></i> {{ __('Team list') }}
    </a>
@endsection

@section('scripts')
    <script type="text/javascript">
        $(function () {

        });
    </script>
@endsection

@section('content')
    <div class="container text-center">
        @isset($team)
            @isset($team->iconfile)
                <p>
                    <img src="{{ $model->iconfile->getUrl() }}" style="max-width: 256px; max-height: 256px;"
                         alt="{{ __('Team logo') }}"/>
                </p>
            @endisset
            <p>
                {{ sprintf(__('You have been invited to join team %s.'), $team->name) }}
                @auth
                    {{ __('Accept the invitation to join the team!') }}
                @else
                    {{ __('Login or register on Keystone.guru to join the team, it\'s free!') }}
                @endauth
            </p>
            <div class="row">
                <div class="col">
                    @auth
                        <a href="{{ route('team.invite.accept', ['invitelink' => $team->invite_code ]) }}" class="btn btn-primary col-lg-auto">
                            <i class="fas fa-user-plus"></i> {{ __('Accept invitation') }}
                        </a>
                    @else
                        <button class="btn btn-primary col-lg-auto" data-toggle="modal" data-target="#login_modal">
                            {{ __('Login') }}
                        </button>
                        <button class="btn btn-primary col-lg-auto" data-toggle="modal" data-target="#register_modal">
                            {{ __('Register now!') }}
                        </button>
                    @endauth
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
