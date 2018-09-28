@extends('layouts.app', ['wide' => true])

@section('header-title', __('My profile'))

@section('content')

    @php( $user = Auth::getUser() )
    {{ Form::model($user, ['route' => ['profile.update', $user->name], 'method' => 'patch']) }}

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('name', __('Name')) !!}
        {!! Form::text('name', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
        {!! Form::label('email', __('Email')) !!}
        {!! Form::text('email', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'email'])
    </div>

    {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}

    <div class="mt-4">
        <h3>Patreon</h3>
        <a class="btn patreon-color text-white" href="{{
            'https://patreon.com/oauth2/authorize?' . http_build_query(
                ['response_type' => 'code',
                'client_id' => env('PATREON_CLIENT_ID'),
                'redirect_uri' => $_SERVER['URL_HOST'] . 'patreon-link',
                'state' => csrf_token()
                ])
            }}" target="_blank">{{'Link to Patreon'}}</a>

        <p class="text-primary mt-2">
            <span class="text-warning"><i class="fa fa-exclamation-triangle"></i></span>
            {{ __('Patreon implementation is experimental. If your rewards are not available after linking with your Patreon, please contact me directly on Discord and I will fix it for you.') }}
        </p>
    </div>

    {!! Form::close() !!}

    {{ Form::model($user, ['route' => ['profile.changepassword', $user->name], 'method' => 'patch']) }}
    <div class="mt-4">
        <h3>{{ __('Change password') }}</h3>

        <div class="form-group{{ $errors->has('current_password') ? ' has-error' : '' }}">
            {!! Form::label('current_password', __('Current password')) !!}
            {!! Form::password('current_password', ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'current_password'])
        </div>

        <div class="form-group{{ $errors->has('new_password') ? ' has-error' : '' }}">
            {!! Form::label('new_password', __('New password')) !!}
            {!! Form::password('new_password', ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'new_password'])
        </div>


        <div class="form-group{{ $errors->has('new_password-confirm') ? ' has-error' : '' }}">
            {!! Form::label('new_password-confirm', __('New password (confirm)')) !!}
            {!! Form::password('new_password-confirm', ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'new_password-confirm'])
        </div>
    </div>

    {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}

    {!! Form::close() !!}

    <div class="mt-4">
        <h1>{{ __('My dungeonroutes') }}</h1>

        @include('common.dungeonroute.table', ['edit_links' => true])
    </div>
@endsection