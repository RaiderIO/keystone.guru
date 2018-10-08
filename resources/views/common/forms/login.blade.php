<?php
$modal = isset($modal) ? $modal : false;
$modalClass = $modal ? 'modal-' : '';
$width = $modal ? '12' : '6';
?>

<form class="form-horizontal" method="POST" action="{{ route('login') }}">
    {{ csrf_field() }}

    <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
        <label for="{{ $modalClass }}login_email" class="control-label">E-Mail Address</label>

        <div class="col-md-{{ $width }}">
            <input id="{{ $modalClass }}login_email" type="email" class="form-control" name="email" value="{{ old('email') }}" required
                   autofocus>
        </div>
    </div>

    <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
        <label for="{{ $modalClass }}login_password" class="control-label">Password</label>

        <div class="col-md-{{ $width }}">
            <input id="{{ $modalClass }}login_password" type="password" class="form-control" name="password" required>
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-{{ $width }} {{ $modal ? 'col-md-offset-4' : '' }}">
            <div class="checkbox">
                <label for="{{ $modalClass }}login_remember">
                    <input id="{{ $modalClass }}login_remember" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    {{ __('Remember me') }}
                </label>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary">
                {{ __('Login') }}
            </button>

            <a class="btn btn-link" href="{{ route('password.request') }}">
                {{ __('Forgot your password?') }}
            </a>
        </div>
    </div>
</form>