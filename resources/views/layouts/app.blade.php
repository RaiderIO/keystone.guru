<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/lib.css') }}" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
    <link rel="icon" href="/images/icon/favicon.ico">
    @yield('head')
</head>
<body>
<div id="app">
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom border-dark">
        <div class="container">
            <a class="navbar-brand" href="/">{{ config('app.name', 'Laravel') }}</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item active">
                        <a class="nav-link active" href="{{ route('dungeonroutes') }}">{{ __('Routes') }}</a>
                </ul>
                <ul class="navbar-nav">
                    @if (Auth::guest())
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">{{__('Login')}}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">{{__('Register')}}</a>
                        </li>
                    @else
                        <li class="nav-item">
                            <div style="padding: 7px">
                                <a href="{{ route('dungeonroute.new') }}" class="btn btn-success text-white"
                                   role="button"><i class="fas fa-plus"></i> {{__('Create route')}}</a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="user_icon">
                                    <i class="fas fa-user"></i>
                                </span>
                                {{ Auth::user()->name }}
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                @if( Auth::user()->can('read-expansions') )
                                <a class="dropdown-item" href="{{ route('admin.expansions') }}">{{__('View expansions')}}</a>
                                @endif
                                @if( Auth::user()->can('read-dungeons') )
                                <a class="dropdown-item" href="{{ route('admin.dungeons') }}">{{__('View dungeons')}}</a>
                                @endif
                                @if( Auth::user()->can('read-npcs') )
                                    <a class="dropdown-item" href="{{ route('admin.npcs') }}">{{__('View NPCs')}}</a>
                                @endif
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">My profile</a>
                                <div class="dropdown-divider"></div>

                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    Logout
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                      style="display: none;">
                                    {{ csrf_field() }}
                                </form>
                            </div>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <div class="container<?php echo(isset($wide) && $wide ? "-fluid" : ""); ?>">
        <div class="row">
            <div class="card mt-3 mb-3 <?php echo(isset($wide) && $wide ? "col-md-12 ml-3 mr-3" : "col-md-8 offset-md-2"); ?>">
                <div class="card-body">
                    <div class="card-header <?php echo(isset($wide) && $wide ? "panel-heading-wide" : ""); ?>">
                        <div class="row">
                            <div class="col-lg-6">
                                <h4>@yield('header-title')</h4>
                            </div>
                            <div class="ml-auto">
                                @yield('header-addition')
                            </div>
                        </div>

                    </div>

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
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <div class="container text-center">
        <hr/>
        <div class="row">
            <div class="col-lg-12">
                <div class="col-md-3">
                    <ul class="nav nav-pills nav-stacked">
                        <li><a href="#">About</a></li>
                        <li><a href="#">News</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <ul class="nav nav-pills nav-stacked">
                        <li><a href="#">Product for Mac</a></li>
                        <li><a href="#">Product for Windows</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <ul class="nav nav-pills nav-stacked">
                        <li><a href="#">Help</a></li>
                        <li><a href="#">Presentations</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <ul class="nav nav-pills nav-stacked">
                        <li>
                            <a href="https://">
                                <i class="fab fa-github"> Github</i>
                            </a>
                        </li>
                        <li><a href="#">Developer API</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-lg-12"> <!-- -->
                <ul class="nav nav-pills nav-justified">
                    <li><a href="/">©{{ date('Y') }} {{ Config::get('app.name') }}</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Privacy</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/lib.js') }}"></script>
<!-- Custom last; may require anything from the above -->
<script src="{{ asset('js/custom.js') }}"></script>
@yield('scripts')
</body>
</html>
