
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('status'))
    <div id="app_session_status_message" class="alert alert-success">
        {{ session('status') }}
    </div>
@endif

@if (session('warning'))
    <div id="app_session_warning_message" class="alert alert-warning">
        {{ session('warning') }}
    </div>
@endif