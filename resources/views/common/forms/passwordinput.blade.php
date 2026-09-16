<?php
/**
 * A required password input with a button that reveals what was typed, plus its field error.
 *
 * @var string      $inputId
 * @var string      $name
 * @var string      $autocomplete
 * @var string|null $errorKey
 */
$errorKey ??= $name;
$errors   ??= collect();
$hasError = $errors->has($errorKey);
?>
<div class="input-group">
    <input id="{{ $inputId }}" type="password"
           class="form-control{{ $hasError ? ' is-invalid' : '' }}" name="{{ $name }}"
           required autocomplete="{{ $autocomplete }}"
           @if($hasError) aria-invalid="true" @endif>
    <button id="{{ $inputId }}_reveal" type="button" class="btn btn-password-reveal"
            aria-controls="{{ $inputId }}" aria-pressed="false"
            aria-label="{{ __('view_common.forms.passwordinput.show_password') }}">
        <i class="fas fa-eye" aria-hidden="true"></i>
    </button>
</div>
@include('common.forms.form-error', ['key' => $errorKey])

{{-- 'modal' pinned: the login/register partials set their own $modal flag, which would otherwise
     leak into this include as a selector to defer activation to --}}
@include('common.general.inline', ['path' => 'common/forms/passwordinput', 'modal' => false, 'options' => [
    'inputSelector'  => '#' . $inputId,
    'toggleSelector' => '#' . $inputId . '_reveal',
]])
