<?php
/** @var $user \App\User */
?>
<div class="tab-pane fade" id="change-password" role="tabpanel" aria-labelledby="change-password-tab">
    <h4>
        {{ __('views/profile.edit.change_password') }}
    </h4>
    {{--$user->email is intended, since that is the actual username--}}
    {{ Form::model($user, ['route' => ['profile.changepassword', $user->name], 'method' => 'patch']) }}
    {!! Form::hidden('username', $user->email) !!}
    <div class="form-group{{ $errors->has('current_password') ? ' has-error' : '' }}">
        {!! Form::label('current_password', __('views/profile.edit.current_password')) !!}
        {!! Form::password('current_password', ['class' => 'form-control', 'autocomplete' => 'current-password']) !!}
        @include('common.forms.form-error', ['key' => 'current_password'])
    </div>

    <div class="form-group{{ $errors->has('new_password') ? ' has-error' : '' }}">
        {!! Form::label('new_password', __('views/profile.edit.new_password')) !!}
        {!! Form::password('new_password', ['id' => 'new_password', 'class' => 'form-control', 'autocomplete' => 'new-password']) !!}
        @include('common.forms.form-error', ['key' => 'new_password'])
    </div>


    <div class="form-group{{ $errors->has('new_password-confirm') ? ' has-error' : '' }}">
        {!! Form::label('new_password-confirm', __('views/profile.edit.new_password_confirm')) !!}
        {!! Form::password('new_password-confirm', ['class' => 'form-control', 'autocomplete' => 'new-password']) !!}
        @include('common.forms.form-error', ['key' => 'new_password-confirm'])
    </div>

    {!! Form::submit(__('views/profile.edit.submit'), ['class' => 'btn btn-info']) !!}

    {!! Form::close() !!}
</div>
