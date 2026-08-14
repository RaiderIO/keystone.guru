<?php
$modal      ??= false;
$modalClass = $modal ? 'modal-' : '';
$redirect   ??= Request::get('redirect', Request::getPathInfo());
// May be set if the user failed his initial login and needs another passthrough of redirect
$redirect = old('redirect', $redirect);
$errors   ??= collect();
// Set by AuthFormComposer - the home page URL when the current page is guest-only, null otherwise
$authSuccessUrl ??= null;
?>

<div class="row">
    <div class="col-12 col-lg-6">
        <form id="{{ $modalClass }}login_form" method="POST"
              action="{{ route('login', ['redirect' => $redirect]) }}">
            {{ csrf_field() }}
            <h3>
                {{ __('view_common.forms.login.login') }}
            </h3>

            <div class="mb-3">
                <label for="{{ $modalClass }}login_email" class="form-label">
                    {{ __('view_common.forms.login.email_address') }}
                </label>

                <input id="{{ $modalClass }}login_email" type="email"
                       class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email"
                       value="{{ old('email') }}" required autofocus autocomplete="username email"
                       @if($errors->has('email')) aria-invalid="true" @endif>
                @include('common.forms.form-error', ['key' => 'email'])
            </div>

            <div class="mb-3">
                <label for="{{ $modalClass }}login_password" class="form-label">
                    {{ __('view_common.forms.login.password') }}
                </label>

                <input id="{{ $modalClass }}login_password" type="password"
                       class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password"
                       autocomplete="current-password" required
                       @if($errors->has('password')) aria-invalid="true" @endif>
                @include('common.forms.form-error', ['key' => 'password'])
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input id="{{ $modalClass }}login_remember" type="checkbox"
                           name="remember" class="form-check-input" {{ old('remember') ? 'checked' : '' }}>
                    <label for="{{ $modalClass }}login_remember" class="form-check-label">
                        {{ __('view_common.forms.login.remember_me') }}
                    </label>
                </div>
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary">
                    {{ __('view_common.forms.login.login') }}
                </button>

                <a class="btn btn-link" href="{{ route('password.request') }}">
                    {{ __('view_common.forms.login.forgot_your_password') }}
                </a>
            </div>

            <div>
                {{ __('view_common.forms.login.no_account_yet') }}
                <a id="{{ $modalClass }}login_register_link" href="{{ route('register') }}">
                    {{ __('view_common.forms.login.register_now') }}
                </a>
            </div>
        </form>
    </div>
    <div class="col-12 col-lg-6 auth-form-divider">
        <h3>
            {{ __('view_common.forms.login.login_through_oauth2') }}
        </h3>
        @include('common.forms.oauth', ['idPrefix' => $modalClass . 'login_'])
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

    {{-- In modal context the cross-link swaps modals rather than navigating away, which would
         throw away the page state the modal exists to preserve --}}
    @include('common.general.inline', ['path' => 'common/forms/modalswap', 'modal' => '#login_modal', 'options' => [
        'linkSelector' => '#' . $modalClass . 'login_register_link',
        'fromModal'    => '#login_modal',
        'toModal'      => '#register_modal',
    ]])
@endif
