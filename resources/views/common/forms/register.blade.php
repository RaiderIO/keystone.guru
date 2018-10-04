<?php
$modal = isset($modal) ? $modal : false;
$width = $modal ? '12' : '6';
?>

<form class="form-horizontal" method="POST" action="{{ route('register') }}">
    {{ csrf_field() }}

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        <label for="register_name" class="control-label">Name</label>

        <div class="col-md-{{ $width }}">
            <input id="register_name" type="text" class="form-control" name="name" value="{{ old('name') }}" required autofocus>

            @if ($errors->has('name'))
                <span class="help-block">
                    <strong>{{ $errors->first('name') }}</strong>
                </span>
            @endif
        </div>
    </div>

    <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
        <label for="register_email" class="control-label">E-Mail Address</label>

        <div class="col-md-{{ $width }}">
            <input id="register_email" type="email" class="form-control" name="email" value="{{ old('email') }}" required>

            @if ($errors->has('email'))
                <span class="help-block">
                    <strong>{{ $errors->first('email') }}</strong>
                </span>
            @endif
        </div>
    </div>

    <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
        <label for="register_password" class="control-label">Password</label>

        <div class="col-md-{{ $width }}">
            <input id="register_password" type="password" class="form-control" name="password" required>

            @if ($errors->has('password'))
                <span class="help-block">
                    <strong>{{ $errors->first('password') }}</strong>
                </span>
            @endif
        </div>
    </div>

    <div class="form-group">
        <label for="register_password-confirm" class="control-label">Confirm Password</label>

        <div class="col-md-{{ $width }}">
            <input id="register_password-confirm" type="password" class="form-control" name="password_confirmation" required>
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary">
                Register
            </button>
        </div>
    </div>
</form>