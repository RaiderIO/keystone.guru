<?php
/** @var \App\User $user */
$user = Auth::getUser();
$isOAuth = $user->password === '';
?>

@extends('layouts.app', ['wide' => true, 'title' => __('Profile')])

@section('header-title', sprintf(__('%s\'s profile'), $user->name))

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            // Code for base app
            var appCode = _inlineManager.getInlineCode('layouts/app');
            appCode._newPassword('#new_password');
        });
    </script>
@endsection


<!-- Modal team select -->
@section('modal-content')
    <ul>
        @foreach($user->teams as $team)
            <?php /** @var $team \App\Models\Team */?>
            <li>{{ $team->name }}</li>
        @endforeach
    </ul>
@overwrite
<!-- END modal team select -->
@section('content')
    @include('common.general.modal', ['id' => 'team_select_modal'])

    <div class="container">
        <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="profile-tab" data-toggle="tab" href="#profile" role="tab"
                   aria-controls="profile" aria-selected="true"><i class="fas fa-user"></i> {{ __('Profile') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="patreon-tab" data-toggle="tab" href="#patreon" role="tab"
                   aria-controls="patreon" aria-selected="false"><i class="fab fa-patreon"></i> {{ __('Patreon') }}</a>
            </li>
            @if(!$isOAuth)
                <li class="nav-item">
                    <a class="nav-link" id="change-password-tab" data-toggle="tab" href="#change-password" role="tab"
                       aria-controls="change-password" aria-selected="false"><i
                                class="fas fa-key"></i> {{ __('Change password') }}</a>
                </li>
            @endif
            <li class="nav-item">
                <a class="nav-link" id="privacy-tab" data-toggle="tab" href="#privacy" role="tab"
                   aria-controls="contact" aria-selected="false"><i class="fas fa-user-secret"></i> {{ __('Privacy') }}
                </a>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">

            <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                {{ Form::model($user, ['route' => ['profile.update', $user->name], 'method' => 'patch']) }}
                @if($isOAuth && !$user->changed_username)
                    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                        <label for="name">
                            {{ __('Username') }}
                            <i class="fas fa-info-circle" data-toggle="tooltip"
                               title="{{ __('Since you logged in using an external Authentication service, you may change your username once.') }}"></i>
                        </label>
                        {!! Form::text('name', null, ['class' => 'form-control']) !!}
                        @include('common.forms.form-error', ['key' => 'name'])
                    </div>
                @endif
                @if(!$isOAuth)
                    <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                        {!! Form::label('email', __('Email')) !!}
                        {!! Form::text('email', null, ['class' => 'form-control']) !!}
                        @include('common.forms.form-error', ['key' => 'email'])
                    </div>
                @endif
                <div class="form-group{{ $errors->has('game_server_region_id') ? ' has-error' : '' }}">
                    {!! Form::label('game_server_region_id', __('Region')) !!}
                    {!! Form::select('game_server_region_id', array_merge(['-1' => __('Select region')], \App\Models\GameServerRegion::all()->pluck('name', 'id')->toArray()), null, ['class' => 'form-control']) !!}
                    @include('common.forms.form-error', ['key' => 'game_server_region_id'])
                </div>
                <div class="form-group{{ $errors->has('timezone') ? ' has-error' : '' }}">
                    @include('common.forms.timezoneselect', ['selected' => $user->timezone])
                </div>

                {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}
                {!! Form::close() !!}
            </div>

            <div class="tab-pane fade" id="patreon" role="tabpanel" aria-labelledby="patreon-tab">
                @isset($user->patreondata)
                    <a class="btn patreon-color text-white" href="{{ route('patreon.unlink') }}" target="_blank">
                        {{ __('Unlink from Patreon') }}
                    </a>

                    <p class="mt-2">
                        <span class="text-info"><i class="fa fa-check-circle"></i></span>
                        {{ __('Your account is linked to Patreon. Thank you!') }}
                    </p>
                @else
                    <a class="btn patreon-color text-white" href="{{
                        'https://patreon.com/oauth2/authorize?' . http_build_query(
                            ['response_type' => 'code',
                            'client_id' => env('PATREON_CLIENT_ID'),
                            'redirect_uri' => route('patreon.link'),
                            'state' => csrf_token()
                            ])
                        }}" target="_blank">{{ __('Link to Patreon') }}</a>

                    <p class="mt-2">
                        <span class="text-info"><i class="fa fa-info-circle"></i></span>
                        {{ __('In order to claim your Patreon rewards, you need to link your Patreon account') }}
                    </p>
                @endisset
                <p class="text-warning mt-2">
                    <i class="fa fa-exclamation-triangle"></i>
                    {{ __('Patreon implementation is experimental. If your rewards are not available after linking with your Patreon, please contact me directly on Discord or Patreon and I will fix it for you.') }}
                </p>
            </div>

            @if(!$isOAuth)
                <div class="tab-pane fade" id="change-password" role="tabpanel" aria-labelledby="change-password-tab">
                    {{--$user->email is intended, since that is the actual username--}}
                    {{ Form::model($user, ['route' => ['profile.changepassword', $user->name], 'method' => 'patch']) }}
                    {!! Form::hidden('username', $user->email) !!}
                    <div class="form-group{{ $errors->has('current_password') ? ' has-error' : '' }}">
                        {!! Form::label('current_password', __('Current password')) !!}
                        {!! Form::password('current_password', ['class' => 'form-control', 'autocomplete' => 'current-password']) !!}
                        @include('common.forms.form-error', ['key' => 'current_password'])
                    </div>

                    <div class="form-group{{ $errors->has('new_password') ? ' has-error' : '' }}">
                        {!! Form::label('new_password', __('New password')) !!}
                        {!! Form::password('new_password', ['id' => 'new_password', 'class' => 'form-control', 'autocomplete' => 'new-password']) !!}
                        @include('common.forms.form-error', ['key' => 'new_password'])
                    </div>


                    <div class="form-group{{ $errors->has('new_password-confirm') ? ' has-error' : '' }}">
                        {!! Form::label('new_password-confirm', __('New password (confirm)')) !!}
                        {!! Form::password('new_password-confirm', ['class' => 'form-control', 'autocomplete' => 'new-password']) !!}
                        @include('common.forms.form-error', ['key' => 'new_password-confirm'])
                    </div>

                    {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}

                    {!! Form::close() !!}
                </div>
            @endif

            <div class="tab-pane fade" id="privacy" role="tabpanel" aria-labelledby="privacy-tab">
                {{ Form::model($user, ['route' => ['profile.updateprivacy', $user->name], 'method' => 'patch']) }}
                <div class="form-group{{ $errors->has('analytics_cookie_opt_out') ? ' has-error' : '' }}">
                    {!! Form::label('analytics_cookie_opt_out', __('Google Analytics cookies opt-out')) !!}
                    {!! Form::checkbox('analytics_cookie_opt_out', 1, $user->analytics_cookie_opt_out, ['class' => 'form-control left_checkbox']) !!}
                </div>
                <div class="form-group{{ $errors->has('adsense_no_personalized_ads') ? ' has-error' : '' }}">
                    {!! Form::label('adsense_no_personalized_ads', __('Google Adsense no personalized ads')) !!}
                    {!! Form::checkbox('adsense_no_personalized_ads', 1, $user->adsense_no_personalized_ads, ['class' => 'form-control left_checkbox']) !!}
                </div>
                {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}
                {!! Form::close() !!}
            </div>
        </div>
    </div>

    <div class="mt-4">
        <h3>{{ __('My routes') }}</h3>

        @include('common.dungeonroute.table', ['view' => 'profile'])
    </div>
@endsection