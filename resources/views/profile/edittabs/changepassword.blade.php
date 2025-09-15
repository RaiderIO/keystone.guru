<?php

use App\Models\User;

/**
 * @var User $user
 */
?>
<div class="tab-pane fade" id="change-password" role="tabpanel" aria-labelledby="change-password-tab">
    <h4>
        {{ __('view_profile.edit.change_password') }}
    </h4>
    {{--$user->email is intended, since that is the actual username--}}
    {{ html()->modelForm($user, 'PATCH', route('profile.changepassword', $user->name))->open() }}
    {{ html()->hidden('username', $user->email) }}
    <div class="form-group{{ $errors->has('current_password') ? ' has-error' : '' }}">
        {{ html()->label(__('view_profile.edit.current_password'), 'current_password') }}
        {{ html()->password('current_password')->class('form-control')->attribute('autocomplete', 'current-password') }}
        @include('common.forms.form-error', ['key' => 'current_password'])
    </div>

    <div class="form-group{{ $errors->has('new_password') ? ' has-error' : '' }}">
        {{ html()->label(__('view_profile.edit.new_password'), 'new_password') }}
        {{ html()->password('new_password')->id('new_password')->class('form-control')->attribute('autocomplete', 'new-password') }}
        @include('common.forms.form-error', ['key' => 'new_password'])
    </div>


    <div class="form-group{{ $errors->has('new_password-confirm') ? ' has-error' : '' }}">
        {{ html()->label(__('view_profile.edit.new_password_confirm'), 'new_password-confirm') }}
        {{ html()->password('new_password-confirm')->class('form-control')->attribute('autocomplete', 'new-password') }}
        @include('common.forms.form-error', ['key' => 'new_password-confirm'])
    </div>

    {{ html()->input('submit')->value(__('view_profile.edit.submit'))->class('btn btn-info') }}

    {{ html()->closeModelForm() }}
</div>
