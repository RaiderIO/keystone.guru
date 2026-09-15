@extends('layouts.sitepage', ['title' => __('view_auth.passwords.reset.title')])

@section('header-title', __('view_auth.passwords.reset.header'))
@section('content')
    <form id="reset_password_form" method="POST" action="{{ route('password.update') }}" class="auth-form">
        {{ csrf_field() }}

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-3">
            <label for="email" class="form-label">{{ __('view_auth.passwords.reset.email_address') }}</label>

            <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                   name="email" value="{{ $email ?? old('email') }}"
                   required autofocus autocomplete="email"
                   @if($errors->has('email')) aria-invalid="true" @endif>

            @include('common.forms.form-error', ['key' => 'email'])
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">{{ __('view_auth.passwords.reset.password') }}</label>

            @include('common.forms.passwordinput', [
                'inputId'      => 'password',
                'name'         => 'password',
                'autocomplete' => 'new-password',
            ])
        </div>

        <div class="mb-3">
            <label for="password-confirm" class="form-label">
                {{ __('view_auth.passwords.reset.confirm_password') }}
            </label>

            @include('common.forms.passwordinput', [
                'inputId'      => 'password-confirm',
                'name'         => 'password_confirmation',
                'autocomplete' => 'new-password',
            ])
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">
                {{ __('view_auth.passwords.reset.reset_password') }}
            </button>
        </div>
    </form>
@endsection
