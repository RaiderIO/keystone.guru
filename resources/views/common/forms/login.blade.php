<?php
$modal = isset($modal) ? $modal : false;
$width = $modal ? '12' : '6';
?>

<form class="form-horizontal" method="POST" action="{{ route('login') }}">
    {{ csrf_field() }}

    <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
        <label for="login_email" class="control-label">E-Mail Address</label>

        <div class="col-md-{{ $width }}">
            <input id="login_email" type="email" class="form-control" name="email" value="{{ old('email') }}" required
                   autofocus>

            @if ($errors->has('email'))
                <span class="help-block">
                    <strong>{{ $errors->first('email') }}</strong>
                </span>
            @endif
        </div>
    </div>

    <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
        <label for="login_password" class="control-label">Password</label>

        <div class="col-md-{{ $width }}">
            <input id="login_password" type="password" class="form-control" name="password" required>

            @if ($errors->has('password'))
                <span class="help-block">
                    <strong>{{ $errors->first('password') }}</strong>
                </span>
            @endif
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-{{ $width }} {{ $modal ? 'col-md-offset-4' : '' }}">
            <div class="checkbox">
                <label for="login_remember">
                    <input id="login_remember" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    Remember Me
                </label>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary">
                Login
            </button>

            <a class="btn btn-link" href="{{ route('password.request') }}">
                Forgot Your Password?
            </a>
        </div>
    </div>
</form>