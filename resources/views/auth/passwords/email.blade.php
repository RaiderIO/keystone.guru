@extends('layouts.sitepage', ['title' => __('views/auth.passwords.email.title')])

@section('header-title', __('views/auth.passwords.email.header'))
@section('content')
<form class="form-horizontal" method="POST" action="{{ route('password.email') }}">
    {{ csrf_field() }}

    <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
        <label for="email" class="col-md-4 control-label">{{ __('views/auth.passwords.email.email_address') }}</label>

        <div class="col-md-6">
            <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required>

            @if ($errors->has('email'))
                <span class="help-block">
                    <strong>{{ $errors->first('email') }}</strong>
                </span>
            @endif
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
            <button type="submit" class="btn btn-primary">
                {{ __('views/auth.passwords.email.send_password_reset_link') }}
            </button>
        </div>
    </div>
</form>
@endsection
