<?php
$modal      ??= false;
$modalClass = $modal ? 'modal-' : '';
$width      = $modal ? '12' : '6';
$redirect   ??= Request::get('redirect', Request::getPathInfo());
// May be set if the user failed his initial login and needs another passthrough of redirect
$redirect = old('redirect', $redirect);
$errors   ??= collect();
// Set by AuthFormComposer - the home page URL when the current page is guest-only, null otherwise
$authSuccessUrl ??= null;
?>

<div class="row">
    <div class="col">
        <form id="{{ $modalClass }}login_form" class="form-horizontal" method="POST"
              action="{{ route('login', ['redirect' => $redirect]) }}">
            {{ csrf_field() }}
            <h3>
                {{ __('view_common.forms.login.login') }}
            </h3>

            <div class="mb-3">
                <label for="{{ $modalClass }}login_email" class="control-label">
                    {{ __('view_common.forms.login.email_address') }}
                </label>

                <div class="col col-xl-{{ $width }}">
                    <input id="{{ $modalClass }}login_email" type="email"
                           class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email"
                           value="{{ old('email') }}" required autofocus autocomplete="username email"
                           @if($errors->has('email')) aria-invalid="true" @endif>
                    @include('common.forms.form-error', ['key' => 'email'])
                </div>
            </div>

            <div class="mb-3">
                <label for="{{ $modalClass }}login_password" class="control-label">
                    {{ __('view_common.forms.login.password') }}
                </label>

                <div class="col col-xl-{{ $width }}">
                    <input id="{{ $modalClass }}login_password" type="password"
                           class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password"
                           autocomplete="current-password" required
                           @if($errors->has('password')) aria-invalid="true" @endif>
                    @include('common.forms.form-error', ['key' => 'password'])
                </div>
            </div>

            <div class="mb-3">
                <div class="col col-xl-{{ $width }}">
                    <div class="form-check">
                        <input id="{{ $modalClass }}login_remember" type="checkbox"
                               name="remember" class="form-check-input" {{ old('remember') ? 'checked' : '' }}>
                        <label for="{{ $modalClass }}login_remember" class="form-check-label">
                            {{ __('view_common.forms.login.remember_me') }}
                        </label>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="col-xl-12">
                    <button type="submit" class="btn btn-primary">
                        {{ __('view_common.forms.login.login') }}
                    </button>

                    <a class="btn btn-link" href="{{ route('password.request') }}">
                        {{ __('view_common.forms.login.forgot_your_password') }}
                    </a>
                </div>
            </div>
        </form>
    </div>
    <div class="col border-start border-white">
        <h3>
            {{ __('view_common.forms.login.login_through_oauth2') }}
        </h3>
        @include('common.forms.oauth')
    </div>
</div>

@if ($modal)
    {{-- Submit over AJAX so a failed login renders its errors inside the modal instead of ejecting
         the user to the login page (and losing their page state) --}}
    @include('common.general.inline', ['path' => 'common/forms/authform', 'modal' => '#login_modal', 'options' => [
        'formSelector' => '#' . $modalClass . 'login_form',
        // Non-null when the current page is guest-only: reloading it would bounce the
        // now-authenticated user off the `guest` middleware, and that extra hop eats the flashed
        // status message. Go to the home page directly instead.
        'successUrl'   => $authSuccessUrl,
    ]])
@endif
