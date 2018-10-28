<?php
$modal = isset($modal) ? $modal : false;
$modalClass = $modal ? 'modal-' : '';
$width = $modal ? '12' : '6';
?>

@section('scripts')
    <script>
        $(function () {
            $(document).on('submit', '#{{ $modalClass }}register_form ', function () {
                // Defined in scripts.blade
                $('#{{ $modalClass }}legal_agreed_ms').val(new Date().getTime() - _legalStartTimer);
            });
        });
    </script>
@endsection

<form id="{{ $modalClass }}register_form" class="form-horizontal" method="POST" action="{{ route('register') }}">
    {{ csrf_field() }}

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        <label for="{{ $modalClass }}register_name" class="control-label">{{ __('Username') }}</label>

        <div class="col-md-{{ $width }}">
            <input id="{{ $modalClass }}register_name" type="text" class="form-control" name="name"
                   value="{{ old('name') }}" required autofocus>
        </div>
    </div>

    <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
        <label for="{{ $modalClass }}register_email" class="control-label">{{ __('E-mail address') }}</label>

        <div class="col-md-{{ $width }}">
            <input id="{{ $modalClass }}register_email" type="email" class="form-control" name="email"
                   value="{{ old('email') }}" required>
        </div>
    </div>

    <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
        <label for="{{ $modalClass }}register_password" class="control-label">{{ __('Password') }}</label>

        <div class="col-md-{{ $width }}">
            <input id="{{ $modalClass }}register_password" type="password" class="form-control" name="password"
                   required>
        </div>
    </div>

    <div class="form-group">
        <label for="{{ $modalClass }}register_password-confirm"
               class="control-label">{{ __('Confirm password') }}</label>

        <div class="col-md-{{ $width }}">
            <input id="{{ $modalClass }}register_password-confirm" type="password" class="form-control"
                   name="password_confirmation" required>
        </div>
    </div>

    <div class="form-group">
        <label for="{{ $modalClass }}legal_agreed" class="control-label">
            {!! sprintf(__('I agree with the %s, %s and the %s.'),
             '<a href="' . route('legal.terms') . '">terms of service</a>',
             '<a href="' . route('legal.privacy') . '">privacy policy</a>',
             '<a href="' . route('legal.cookies') . '">cookie policy</a>')
             !!}
        </label>
        {!! Form::checkbox('legal_agreed', 1, 0, ['id' => $modalClass . 'legal_agreed', 'class' => 'form-control left_checkbox']) !!}
        {!! Form::hidden('legal_agreed_ms', -1, ['id' => $modalClass . 'legal_agreed_ms']) !!}
    </div>

    <div class="form-group">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary">
                {{ __('Register') }}
            </button>
        </div>
    </div>
</form>