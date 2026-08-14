@extends('layouts.sitepage', ['title' => __('view_auth.passwords.reset.title')])

@section('header-title', __('view_auth.passwords.reset.header'))
@section('content')
    <form class="form-horizontal" method="POST" action="{{ route('password.update') }}">
        {{ csrf_field() }}

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-3">
            <label for="email"
                   class="col-md-4 control-label">{{ __('view_auth.passwords.reset.email_address') }}</label>

            <div class="col-md-6">
                <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                       name="email" value="{{ $email ?? old('email') }}"
                       required autofocus autocomplete="email"
                       @if($errors->has('email')) aria-invalid="true" @endif>

                @include('common.forms.form-error', ['key' => 'email'])
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="col-md-4 control-label">{{ __('view_auth.passwords.reset.password') }}</label>

            <div class="col-md-6">
                <input id="password" type="password"
                       class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password"
                       required autocomplete="new-password"
                       @if($errors->has('password')) aria-invalid="true" @endif>

                @include('common.forms.form-error', ['key' => 'password'])
            </div>
        </div>

        <div class="mb-3">
            <label for="password-confirm"
                   class="col-md-4 control-label">{{ __('view_auth.passwords.reset.confirm_password') }}</label>
            <div class="col-md-6">
                <input id="password-confirm" type="password"
                       class="form-control{{ $errors->has('password_confirmation') ? ' is-invalid' : '' }}"
                       name="password_confirmation" required autocomplete="new-password"
                       @if($errors->has('password_confirmation')) aria-invalid="true" @endif>

                @include('common.forms.form-error', ['key' => 'password_confirmation'])
            </div>
        </div>

        <div class="mb-3">
            <div class="col-md-6 col-md-offset-4">
                <button type="submit" class="btn btn-primary">
                    {{ __('view_auth.passwords.reset.reset_password') }}
                </button>
            </div>
        </div>
    </form>
@endsection
