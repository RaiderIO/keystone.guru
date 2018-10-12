@extends('layouts.app', ['wide' => true])

@section('header-title', __('My profile'))

@section('content')

    @php( $user = Auth::getUser() )
    {{ Form::model($user, ['route' => ['profile.update', $user->name], 'method' => 'patch']) }}

    <div class="container">
        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
            {!! Form::label('email', __('Email')) !!}
            {!! Form::text('email', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'email'])
        </div>

        {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}

        <div class="mt-4">
            <h3>Patreon</h3>
            @isset($user->patreondata)
                <a class="btn patreon-color text-white" href="{{ route('patreon.unlink') }}" target="_blank">
                    {{'Unlink from Patreon'}}
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
            }}" target="_blank">{{'Link to Patreon'}}</a>

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

        {!! Form::close() !!}

        {{ Form::model($user, ['route' => ['profile.changepassword', $user->name], 'method' => 'patch']) }}
        {!! Form::hidden('username', $user->name) !!}
        <div class="mt-4">
            <h3>{{ __('Change password') }}</h3>

            <div class="form-group{{ $errors->has('current_password') ? ' has-error' : '' }}">
                {!! Form::label('current_password', __('Current password')) !!}
                {!! Form::password('current_password', ['class' => 'form-control', 'autocomplete' => 'current-password']) !!}
                @include('common.forms.form-error', ['key' => 'current_password'])
            </div>

            <div class="form-group{{ $errors->has('new_password') ? ' has-error' : '' }}">
                {!! Form::label('new_password', __('New password')) !!}
                {!! Form::password('new_password', ['class' => 'form-control', 'autocomplete' => 'new-password']) !!}
                @include('common.forms.form-error', ['key' => 'new_password'])
            </div>


            <div class="form-group{{ $errors->has('new_password-confirm') ? ' has-error' : '' }}">
                {!! Form::label('new_password-confirm', __('New password (confirm)')) !!}
                {!! Form::password('new_password-confirm', ['class' => 'form-control', 'autocomplete' => 'new-password']) !!}
                @include('common.forms.form-error', ['key' => 'new_password-confirm'])
            </div>
        </div>

        {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}

        {!! Form::close() !!}

    </div>

    <div class="mt-4">
        <h3>{{ __('My dungeonroutes') }}</h3>

        @include('common.dungeonroute.table', ['profile' => true])
    </div>
@endsection