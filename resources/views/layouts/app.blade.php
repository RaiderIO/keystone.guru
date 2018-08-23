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

    @include('common.thirdparty.cookieconsent')
    <?php if( !isset($noads) || (isset($noads) && !$noads) ){ ?>
    @include('common.thirdparty.adsense')
    <?php } ?>
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
                            <a href="{{ route('dungeonroute.new') }}" class="btn btn-success text-white"
                               role="button"><i class="fas fa-plus"></i> {{__('Create route')}}</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <div class="user_icon float-left">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="float-left">
                                    {{ Auth::user()->name }}
                                </div>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                @if( Auth::user()->can('read-expansions') )
                                    <a class="dropdown-item"
                                       href="{{ route('admin.expansions') }}">{{__('View expansions')}}</a>
                                @endif
                                @if( Auth::user()->can('read-dungeons') )
                                    <a class="dropdown-item"
                                       href="{{ route('admin.dungeons') }}">{{__('View dungeons')}}</a>
                                @endif
                                @if( Auth::user()->can('read-npcs') )
                                    <a class="dropdown-item" href="{{ route('admin.npcs') }}">{{__('View NPCs')}}</a>
                                @endif
                                @if( Auth::user()->hasRole('admin')) 
                                    <a class="dropdown-item" href="{{ route('admin.datadump.exportdungeondata') }}">{{__('Export dungeon data')}}</a>
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
            <div class="<?php echo(isset($wide) && $wide ? "flex-fill ml-3 mr-3" : "col-md-8 offset-md-2"); ?>">
                <div class="card mt-3 mb-3">
                    <div class="card-header <?php echo(isset($wide) && $wide ? "panel-heading-wide" : ""); ?>">
                        <div class="row">
                            @hasSection('header-addition')
                                <div class="ml-3">
                                    <h4>@yield('header-title')</h4>
                                </div>
                                <div class="ml-auto">
                                    @yield('header-addition')
                                </div>
                            @else
                                <div class="col-lg-12 text-center">
                                    <h4>@yield('header-title')</h4>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
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
    </div>

    <div class="container text-center">
        <hr/>
        <div class="row">
            <div class="col-md-3">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item"><a class="nav-link" href="#">News</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item"><a class="nav-link" href="{{ route('misc.about') }}">About</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <ul class="nav nav-pills flex-column">
                </ul>
            </div>
            <div class="col-md-3">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="https://github.com/Wotuu/keystone.guru">
                            <i class="fab fa-github"> Github</i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <hr>
        <nav class="nav nav-pills nav-justified">
            <a class="nav-item nav-link" href="/">
                ©{{ date('Y') }} {{ Config::get('app.name') }}
            </a>
            <a class="nav-item nav-link" href="{{ route('legal.terms') }}">{{ __('Terms of Service') }}</a>
            <a class="nav-item nav-link" href="{{ route('legal.privacy') }}">{{ __('Privacy') }}</a>
            <a class="nav-item nav-link" href="{{ route('legal.cookies') }}">{{ __('Cookies Policy') }}</a>
        </nav>
    </div>
</div>

<!-- Scripts -->
<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/lib.js') }}"></script>

    @if (config('app.env') === 'production')
     <?php // Compiled only in production, otherwise include all files as-is to prevent having to recompile everything all the time ?>
    <script src="{{ asset('js/custom.js') }}"></script>

        @else

    <script src="{{ asset('js/custom/constants.js') }}"></script>
     <?php // Include in proper order ?>
    <script src="{{ asset('js/custom/util.js') }}"></script>
    <script src="{{ asset('js/custom/signalable.js') }}"></script>
    <script src="{{ asset('js/custom/dungeonmap.js') }}"></script>
    <script src="{{ asset('js/custom/mapobjectgroupcontrols.js') }}"></script>
    <script src="{{ asset('js/custom/drawcontrols.js') }}"></script>
    <script src="{{ asset('js/custom/mapobject.js') }}"></script>
    <script src="{{ asset('js/custom/enemy.js') }}"></script>
    <script src="{{ asset('js/custom/enemypatrol.js') }}"></script>
    <script src="{{ asset('js/custom/enemypack.js') }}"></script>
    <script src="{{ asset('js/custom/route.js') }}"></script>
    <script src="{{ asset('js/custom/dungeonstartmarker.js') }}"></script>
    <script src="{{ asset('js/custom/dungeonfloorswitchmarker.js') }}"></script>
    <script src="{{ asset('js/custom/admin/enemyattaching.js') }}"></script>
    <script src="{{ asset('js/custom/admin/admindungeonmap.js') }}"></script>
    <script src="{{ asset('js/custom/admin/adminenemy.js') }}"></script>
    <script src="{{ asset('js/custom/admin/adminenemypatrol.js') }}"></script>
    <script src="{{ asset('js/custom/admin/adminenemypack.js') }}"></script>
    <script src="{{ asset('js/custom/admin/admindrawcontrols.js') }}"></script>
    <script src="{{ asset('js/custom/admin/admindungeonstartmarker.js') }}"></script>
    <script src="{{ asset('js/custom/admin/admindungeonfloorswitchmarker.js') }}"></script>
     <?php // Include the rest ?>

    <script src="{{ asset('js/custom/groupcomposition.js') }}"></script>
    <script src="{{ asset('js/custom/mapobjectgroup.js') }}"></script>
    <script src="{{ asset('js/custom/mapobjectgroups/enemymapobjectgroup.js') }}"></script>
    <script src="{{ asset('js/custom/mapobjectgroups/enemypatrolmapobjectgroup.js') }}"></script>
    <script src="{{ asset('js/custom/mapobjectgroups/enemypackmapobjectgroup.js') }}"></script>
    <script src="{{ asset('js/custom/mapobjectgroups/routemapobjectgroup.js') }}"></script>
    <script src="{{ asset('js/custom/mapobjectgroups/dungeonstartmarkermapobjectgroup.js') }}"></script>
    <script src="{{ asset('js/custom/mapobjectgroups/dungeonfloorswitchmarkermapobjectgroup.js') }}"></script>

    @endif
@yield('scripts')
</body>
</html>
