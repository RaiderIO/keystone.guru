<?php
$modal = $modal ?? false;
$modalClass = $modal ? 'modal-' : '';
$width = $modal ? '12' : '6';
$redirect = $redirect ?? Request::get('redirect', Request::getPathInfo());
// May be set if the user failed his initial registration and needs another passthrough of redirect
$redirect = old('redirect', $redirect);
$errors = $errors ?? collect();
?>

@section('scripts')
    @parent

    <script>
        $(function () {
            $(document).on('submit', '#{{ $modalClass }}register_form', function () {
                // Defined in sitescripts.blade
                $('#{{ $modalClass }}legal_agreed_ms').val(new Date().getTime() - _legalStartTimer);
            });
        });
    </script>
@endsection

<div class="row">
    <div class="col">
        <form id="{{ $modalClass }}register_form" class="form-horizontal" method="POST"
              action="{{ route('register', ['redirect' => $redirect]) }}">
            {{ csrf_field() }}
            <h3>
                {{ __('views/common.register.register') }}
            </h3>

            <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                <label for="{{ $modalClass }}register_name" class="control-label">
                    {{ __('views/common.register.username') }} <span class="form-required">*</span>
                    <i class="fas fa-info-circle" data-toggle="tooltip"
                       title="{{__('views/common.register.username_title')}}"></i>
                </label>

                <div class="col-md-{{ $width }}">
                    <input id="{{ $modalClass }}register_name" type="text" class="form-control" name="name"
                           value="{{ old('name') }}" required autofocus autocomplete="username">
                </div>
            </div>

            <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                <label for="{{ $modalClass }}register_email" class="control-label">
                    {{ __('views/common.register.email_address') }} <span class="form-required">*</span>
                    <i class="fas fa-info-circle" data-toggle="tooltip"
                       title="{{__('views/common.register.email_address_title')}}">

                    </i>
                </label>
                <div class="col-md-{{ $width }}">
                    <input id="{{ $modalClass }}register_email" type="email" class="form-control" name="email"
                           value="{{ old('email') }}" required>
                </div>
            </div>

            <div class="form-group{{ $errors->has('region') ? ' has-error' : '' }}">
                <label for="{{ $modalClass }}register_region" class="control-label">
                    {{ __('views/common.forms.register.region') }}
                </label>


                <div class="col-md-{{ $width }}">
                    {!! Form::select('region', array_merge(
                    ['-1' => __('views/common.register.select_region')],
                    \App\Models\GameServerRegion::all()->pluck('name', 'id')->toArray()
                    ), null, ['class' => 'form-control']) !!}
                </div>
            </div>

            <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                <label for="{{ $modalClass }}register_password" class="control-label">
                    {{ __('views/common.register.password') }} <span class="form-required">*</span>
                </label>

                <div class="col-md-{{ $width }}">
                    <input id="{{ $modalClass }}register_password" type="password" class="form-control" name="password"
                           required autocomplete="new-password">
                </div>
            </div>

            <div class="form-group">
                <label for="{{ $modalClass }}register_password-confirm"
                       class="control-label">
                    {{ __('views/common.register.confirm_password') }} <span class="form-required">*</span>
                </label>

                <div class="col-md-{{ $width }}">
                    <input id="{{ $modalClass }}register_password-confirm" type="password" class="form-control"
                           name="password_confirmation" required autocomplete="new-password">
                </div>
            </div>

            <div class="form-group">
                <label for="{{ $modalClass }}legal_agreed" class="control-label">
                    {!! sprintf(__('views/common.register.legal_agree'),
                     '<a href="' . route('legal.terms') . '">' . __('views/common.register.terms_of_service') . '</a>',
                     '<a href="' . route('legal.privacy') . '">' . __('views/common.register.privacy_policy') . '</a>',
                     '<a href="' . route('legal.cookies') . '">' . __('views/common.register.cookie_policy') . '</a>')
                     !!}
                </label>
                {!! Form::checkbox('legal_agreed', 1, 0, ['id' => $modalClass . 'legal_agreed', 'class' => 'form-control left_checkbox']) !!}
                {!! Form::hidden('legal_agreed_ms', -1, ['id' => $modalClass . 'legal_agreed_ms']) !!}
            </div>

            <div class="form-group">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        {{ __('views/common.register.register') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
    <div class="col border-left border-white">
        <h3>
            {{ __('views/common.register.register_through_oauth2') }}
        </h3>
        <p>
            {!! sprintf(__('views/common.register.legal_agree_oauth2'),
             '<a href="' . route('legal.terms') . '">' . __('views/common.forms.register.terms_of_service') . '</a>',
             '<a href="' . route('legal.privacy') . '">' . __('views/common.forms.register.privacy_policy') . '</a>',
             '<a href="' . route('legal.cookies') . '">' . __('views/common.forms.register.cookie_policy') . '</a>')
             !!}
        </p>
        <hr>
        @include('common.forms.oauth')
    </div>
</div>