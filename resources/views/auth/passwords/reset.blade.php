@extends('layouts.sitepage', ['title' => __('view_auth.passwords.reset.title')])

@section('header-title', __('view_auth.passwords.reset.header'))
@section('content')
    <form method="POST" action="{{ route('password.update') }}">
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

            <input id="password" type="password"
                   class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password"
                   required autocomplete="new-password"
                   @if($errors->has('password')) aria-invalid="true" @endif>

            @include('common.forms.form-error', ['key' => 'password'])
        </div>

        <div class="mb-3">
            <label for="password-confirm" class="form-label">
                {{ __('view_auth.passwords.reset.confirm_password') }}
            </label>

            <input id="password-confirm" type="password"
                   class="form-control{{ $errors->has('password_confirmation') ? ' is-invalid' : '' }}"
                   name="password_confirmation" required autocomplete="new-password"
                   @if($errors->has('password_confirmation')) aria-invalid="true" @endif>

            @include('common.forms.form-error', ['key' => 'password_confirmation'])
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">
                {{ __('view_auth.passwords.reset.reset_password') }}
            </button>
        </div>
    </form>
@endsection
