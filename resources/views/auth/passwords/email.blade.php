@extends('layouts.sitepage', ['title' => __('view_auth.passwords.email.title')])

@section('header-title', __('view_auth.passwords.email.header'))
@section('content')
    <form method="POST" action="{{ route('password.email') }}">
        {{ csrf_field() }}

        <div class="mb-3">
            <label for="email" class="form-label">{{ __('view_auth.passwords.email.email_address') }}</label>

            <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                   name="email" value="{{ old('email') }}" required autocomplete="email"
                   @if($errors->has('email')) aria-invalid="true" @endif>

            @include('common.forms.form-error', ['key' => 'email'])
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">
                {{ __('view_auth.passwords.email.send_password_reset_link') }}
            </button>
        </div>
    </form>
@endsection
