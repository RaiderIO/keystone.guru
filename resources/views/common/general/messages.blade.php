<?php
$center = isset($center) && $center ? true : false;
?>
@isset($errors)
    @if ($errors->any())
        <div class="alert alert-danger {{ $center ? 'text-center' : '' }}">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div id="app_session_status_message" class="alert alert-success {{ $center ? 'text-center' : '' }}">
            <i class="fas fa-check-circle"></i> {{ session('status') }}
        </div>
    @endif

    @if (session('warning'))
        <div id="app_session_warning_message" class="alert alert-warning {{ $center ? 'text-center' : '' }}">
            <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
        </div>
    @endif
@endisset