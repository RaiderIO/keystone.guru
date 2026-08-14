<?php

use App\Models\GameServerRegion;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, GameServerRegion> $allRegions
 **/

$modal      ??= false;
$modalClass = $modal ? 'modal-' : '';
$width      = $modal ? '12' : '6';
$redirect   ??= Request::get('redirect', Request::getPathInfo());
// May be set if the user failed his initial registration and needs another passthrough of redirect
$redirect = old('redirect', $redirect);
$errors   ??= collect();
// Set by AuthFormComposer - the home page URL when the current page is guest-only, null otherwise
$authSuccessUrl ??= null;
?>

<div class="row">
    <div class="col">
        <form id="{{ $modalClass }}register_form" class="form-horizontal" method="POST"
              action="{{ route('register', ['redirect' => $redirect]) }}">
            {{ csrf_field() }}
            <h3>
                {{ __('view_common.forms.register.register') }}
            </h3>

            <div class="mb-3">
                <label for="{{ $modalClass }}register_name" class="control-label">
                    {{ __('view_common.forms.register.username') }} <span class="form-required">*</span>
                    <i class="fas fa-info-circle" data-bs-toggle="tooltip"
                       title="{{__('view_common.forms.register.username_title')}}"></i>
                </label>

                <div class="col-md-{{ $width }}">
                    <input id="{{ $modalClass }}register_name" type="text"
                           class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name"
                           value="{{ old('name') }}" required autofocus autocomplete="username"
                           @if($errors->has('name')) aria-invalid="true" @endif>
                    @include('common.forms.form-error', ['key' => 'name'])
                </div>
            </div>

            <div class="mb-3">
                <label for="{{ $modalClass }}register_email" class="control-label">
                    {{ __('view_common.forms.register.email_address') }} <span class="form-required">*</span>
                    <i class="fas fa-info-circle" data-bs-toggle="tooltip"
                       title="{{__('view_common.forms.register.email_address_title')}}">

                    </i>
                </label>
                <div class="col-md-{{ $width }}">
                    <input id="{{ $modalClass }}register_email" type="email"
                           class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email"
                           value="{{ old('email') }}" required autocomplete="email"
                           @if($errors->has('email')) aria-invalid="true" @endif>
                    @include('common.forms.form-error', ['key' => 'email'])
                </div>
            </div>

            <div class="mb-3">
                <label for="{{ $modalClass }}register_region" class="control-label">
                    {{ __('view_common.forms.register.region') }}
                </label>


                <div class="col-md-{{ $width }}">
                    {{-- The + operator (not array_merge) keeps the region ids as keys - array_merge renumbers numeric keys --}}
                    {{ html()->select('region', ['' => __('view_common.forms.register.select_region')] + $allRegions->mapWithKeys(function (GameServerRegion $region) {
    return [$region->id => __($region->name)];
})->toArray())->id($modalClass . 'register_region')->value(old('region'))->class('form-select' . ($errors->has('region') ? ' is-invalid' : '')) }}
                    @include('common.forms.form-error', ['key' => 'region'])
                </div>
            </div>

            <div class="mb-3">
                <label for="{{ $modalClass }}register_password" class="control-label">
                    {{ __('view_common.forms.register.password') }} <span class="form-required">*</span>
                </label>

                <div class="col-md-{{ $width }}">
                    <input id="{{ $modalClass }}register_password" type="password"
                           class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password"
                           required autocomplete="new-password"
                           @if($errors->has('password')) aria-invalid="true" @endif>
                    @include('common.forms.form-error', ['key' => 'password'])
                </div>
            </div>

            <div class="mb-3">
                <label for="{{ $modalClass }}register_password-confirm"
                       class="control-label">
                    {{ __('view_common.forms.register.confirm_password') }} <span class="form-required">*</span>
                </label>

                <div class="col-md-{{ $width }}">
                    <input id="{{ $modalClass }}register_password-confirm" type="password"
                           class="form-control{{ $errors->has('password_confirmation') ? ' is-invalid' : '' }}"
                           name="password_confirmation" required autocomplete="new-password"
                           @if($errors->has('password_confirmation')) aria-invalid="true" @endif>
                    @include('common.forms.form-error', ['key' => 'password_confirmation'])
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    {{ html()->checkbox('legal_agreed', null, 1)->id($modalClass . 'legal_agreed')->class('form-check-input' . ($errors->has('legal_agreed') ? ' is-invalid' : '')) }}
                    <label for="{{ $modalClass }}legal_agreed" class="form-check-label">
                        {!! sprintf(__('view_common.forms.register.legal_agree'),
                         '<a href="' . route('legal.terms') . '">' . __('view_common.forms.register.terms_of_service') . '</a>',
                         '<a href="' . route('legal.privacy') . '">' . __('view_common.forms.register.privacy_policy') . '</a>',
                         '<a href="' . route('legal.cookies') . '">' . __('view_common.forms.register.cookie_policy') . '</a>')
                         !!}
                    </label>
                    @include('common.forms.form-error', ['key' => 'legal_agreed'])
                </div>
            </div>

            <div class="mb-3">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        {{ __('view_common.forms.register.register') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
    <div class="col border-start border-white">
        <h3>
            {{ __('view_common.forms.register.register_through_oauth2') }}
        </h3>
        <p>
            {!! sprintf(__('view_common.forms.register.legal_agree_oauth2'),
             '<a href="' . route('legal.terms') . '">' . __('view_common.forms.register.terms_of_service') . '</a>',
             '<a href="' . route('legal.privacy') . '">' . __('view_common.forms.register.privacy_policy') . '</a>',
             '<a href="' . route('legal.cookies') . '">' . __('view_common.forms.register.cookie_policy') . '</a>')
             !!}
            {{ __('view_common.forms.oauth.battletag_warning') }}
        </p>
        <hr>
        @include('common.forms.oauth')
    </div>
</div>

@if ($modal)
    {{-- Submit over AJAX so a failed registration renders its errors inside the modal instead of
         ejecting the user to the register page (and losing their page state) --}}
    @include('common.general.inline', ['path' => 'common/forms/authform', 'modal' => '#register_modal', 'options' => [
        'formSelector' => '#' . $modalClass . 'register_form',
        // Non-null when the current page is guest-only: reloading it would bounce the
        // now-authenticated user off the `guest` middleware, and that extra hop eats the flashed
        // status message. Go to the home page directly instead.
        'successUrl'   => $authSuccessUrl,
    ]])
@endif
