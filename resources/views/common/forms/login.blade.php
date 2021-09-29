<?php
$modal      = $modal ?? false;
$modalClass = $modal ? 'modal-' : '';
$width      = $modal ? '12' : '6';
$redirect   = $redirect ?? Request::get('redirect', Request::getPathInfo());
// May be set if the user failed his initial login and needs another passthrough of redirect
$redirect = old('redirect', $redirect);
$errors   = $errors ?? collect();
?>

<div class="row">
    <div class="col">
        <form class="form-horizontal" method="POST"
              action="{{ route('login', ['redirect' => $redirect]) }}">
            {{ csrf_field() }}
            <h3>
                {{ __('views/common.forms.login.login') }}
            </h3>

            <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                <label for="{{ $modalClass }}login_email" class="control-label">
                    {{ __('views/common.forms.login.email_address') }}
                </label>

                <div class="col col-xl-{{ $width }}">
                    <input id="{{ $modalClass }}login_email" type="email" class="form-control" name="email"
                           value="{{ old('email') }}" required autofocus autocomplete="username email">
                </div>
            </div>

            <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                <label for="{{ $modalClass }}login_password" class="control-label">
                    {{ __('views/common.forms.login.password') }}
                </label>

                <div class="col col-xl-{{ $width }}">
                    <input id="{{ $modalClass }}login_password" type="password" class="form-control" name="password"
                           autocomplete="current-password" required>
                </div>
            </div>

            <div class="form-group">
                <label for="{{ $modalClass }}login_remember">
                    {{ __('views/common.forms.login.remember_me') }}
                </label>
                <div class="col col-xl-{{ $width }} {{ $modal ? 'col-md-offset-4' : '' }}">
                    <input id="{{ $modalClass }}login_remember" type="checkbox"
                           name="remember" class="form-control left_checkbox" {{ old('remember') ? 'checked' : '' }}>
                </div>
            </div>

            <div class="form-group">
                <div class="col-xl-12">
                    <button type="submit" class="btn btn-primary">
                        {{ __('views/common.forms.login.login') }}
                    </button>

                    <a class="btn btn-link" href="{{ route('password.request') }}">
                        {{ __('views/common.forms.login.forgot_your_password') }}
                    </a>
                </div>
            </div>
        </form>
    </div>
    <div class="col border-left border-white">
        <h3>
            {{ __('views/common.forms.login.login_through_oauth2') }}
        </h3>
        @include('common.forms.oauth')
    </div>
</div>
