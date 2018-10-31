<?php
$numUserReports = \App\Models\UserReport::where('handled', 0)->count();

$user = \Illuminate\Support\Facades\Auth::user();
// Show the legal modal or not if people didn't agree to it yet
$showLegalModal = isset($showLegalModal) ? $showLegalModal : true;
// Show ads if not set
$noads = isset($noads) ? $noads : false;
// If logged in, check if the user has paid for an ad-free website
$noads = $noads || !Auth::check() ? $noads : $user->hasPaidTier('ad-free');
// Floating navbar or not
$headerFloat = isset($headerFloat) ? $headerFloat : false;
// Custom content or not
$custom = isset($custom) ? $custom : false;
// Wide mode or not (only relevant if custom = false)
$wide = isset($wide) ? $wide : false;
// Show footer or not
$footer = isset($footer) ? $footer : true;
?><!DOCTYPE html>
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
    @if (config('app.env') !== 'production')
        <link href="{{ asset('css/map.css') }}" rel="stylesheet">
        <link href="{{ asset('css/datatables.css') }}" rel="stylesheet">
        <link href="{{ asset('css/classes.css') }}" rel="stylesheet">
        <link href="{{ asset('css/affixes.css') }}" rel="stylesheet">
        <link href="{{ asset('css/specializations.css') }}" rel="stylesheet">
        <link href="{{ asset('css/factions.css') }}" rel="stylesheet">
        <link href="{{ asset('css/raidmarkers.css') }}" rel="stylesheet">
        <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
        <link href="{{ asset('css/home.css') }}" rel="stylesheet">
    @endif
    <link rel="icon" href="/images/icon/favicon.ico">
    @yield('head')

    @include('common.general.scripts', ['showLegalModal' => $showLegalModal])
    @include('common.thirdparty.cookieconsent')
    <?php if(config('app.env') === 'production' ){
    if(!$noads ) {?>
    @include('common.thirdparty.adsense')
    <?php } ?>
    @include('common.thirdparty.analytics')
    <?php } ?>
</head>
<body>
<div id="app">
    <nav class="navbar navbar-expand-lg navbar-dark {{ $headerFloat ? 'fixed-top' : 'bg-dark' }}">
        <div class="container {{ $headerFloat ? 'bg-dark header_container_rounded' : '' }}">
            <a class="navbar-brand" href="/">{{ config('app.name', 'Laravel') }}</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dungeonroutes') }}">{{ __('Routes') }}</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="demo_dropdown" role="button"
                           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Demo') }}
                        </a>

                        <div class="dropdown-menu" aria-labelledby="demo_dropdown">
                            @foreach(\App\Models\DungeonRoute::where('demo', '=', true)->get() as $route)
                                <a class="dropdown-item"
                                   href="{{ route('dungeonroute.view', ['public_key' => $route->public_key]) }}">
                                    {{ $route->dungeon->name }}
                                </a>
                            @endforeach
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('misc.affixes') }}">{{ __('Affixes') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('misc.changelog') }}">{{ __('Changelog') }}</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    @if (Auth::guest())
                        <li class="nav-item mr-2">
                            <a href="{{ route('dungeonroute.try') }}" class="btn btn-primary text-white"
                               role="button">{{__('Try it!')}}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-toggle="modal"
                               data-target="#login_modal">{{__('Login')}}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-toggle="modal"
                               data-target="#register_modal">{{__('Register')}}</a>
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
                                    <a class="dropdown-item"
                                       href="{{ route('admin.datadump.exportdungeondata') }}">{{__('Export dungeon data')}}</a>
                                @endif
                                @if( Auth::user()->hasRole('admin'))
                                    <a class="dropdown-item"
                                       href="{{ route('admin.users') }}">{{__('View users')}}</a>
                                @endif
                                @if( Auth::user()->hasRole('admin'))
                                    <a class="dropdown-item"
                                       href="{{ route('admin.userreports') }}">{{__('View user reports') }}
                                        <span class="badge badge-primary badge-pill">{{ $numUserReports }}</span>
                                    </a>
                                @endif
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">{{ __('My profile') }}</a>
                                <div class="dropdown-divider"></div>

                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    {{ __('Logout') }}
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

    @if (config('app.env') !== 'production' && (Auth::user() === null || !Auth::user()->hasRole('admin')))
        <div class="container-fluid alert alert-warning text-center mt-4">
            {{ __('Warning! You are currently on the development instance of Keystone.guru. This is NOT the main site.') }}
            <br>
            {{ __('If you got here by accident, I\'d be interested in knowing how you got here! Message me on Discord :)') }}
            <br>
            <a href="https://keystone.guru/">{{ __('Take me to the main site!') }}</a>
        </div>
    @endif

    @yield('global-message')

    <?php if( $custom ) { ?>
    @yield('content')
    <?php } else { ?>
    <div class="container-fluid">
        <div class="row">
            <div class="{{ $wide ? "flex-fill ml-lg-3 mr-lg-3" : "col-md-8 offset-md-2" }}">
                <div class="card mt-3 mb-3">
                    <div class="card-header {{ $wide ? "panel-heading-wide" : "" }}">
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
                            <div id="app_session_status_message" class="alert alert-success">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if (session('warning'))
                            <div id="app_session_warning_message" class="alert alert-warning">
                                {{ session('warning') }}
                            </div>
                        @endif

                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>

    <footer class="fixed-bottom">
        <div class="row">
            <div id="fixed_footer_container" class="col-12">
            </div>
        </div>
    </footer>

    @if( $footer )

        <div class="container text-center">
            <hr/>
            <div class="row">
                <div class="col-md-3">
                    <a class="nav-link" href="{{ route('misc.credits') }}">{{ __('Credits') }}</a>
                </div>
                <div class="col-md-3">
                    <a class="nav-link" href="https://www.patreon.com/keystoneguru" target="_blank">
                        <i class="fab fa-patreon"></i> {{ __('Patreon') }}
                    </a>
                </div>
                <div class="col-md-3">
                    <a class="nav-link" href="https://discord.gg/2KtWrqw" target="_blank">
                        <i class="fab fa-discord"></i> {{ __('Discord') }}
                    </a>
                </div>
                <div class="col-md-3">
                    <a class="nav-link" href="https://github.com/Wotuu/keystone.guru" target="_blank">
                        <i class="fab fa-github"></i> {{ __('Github') }}
                    </a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <a class="nav-link" href="{{ route('misc.about') }}">{{ __('About') }}</a>
                </div>
                <div class="col-md-3">
                    <a class="nav-item nav-link" href="{{ route('legal.terms') }}">{{ __('Terms of Service') }}</a>
                </div>
                <div class="col-md-3">
                    <a class="nav-item nav-link" href="{{ route('legal.privacy') }}">{{ __('Privacy') }}</a>
                </div>
                <div class="col-md-3">
                    <a class="nav-item nav-link" href="{{ route('legal.cookies') }}">{{ __('Cookies Policy') }}</a>
                </div>
            </div>
            <div class="row text-center small">
                <div class="col-md-6">
                    <a class="nav-item nav-link" href="{{ route('misc.mapping') }}">{{ __('Mapping Progress') }}</a>
                    <a class="nav-item nav-link" href="/">©{{ date('Y') }} {{ Config::get('app.name') }} v.1.0 </a>
                </div>
                <div class="col-md-6">
                    World of Warcraft, Warcraft and Blizzard Entertainment are trademarks or registered trademarks of
                    Blizzard Entertainment, Inc. in the U.S. and/or other countries. This website is not affiliated with
                    Blizzard Entertainment.
                </div>
            </div>
        </div>
    @endif
</div>

<script id="app_fixed_footer_template" type="text/x-handlebars-template">
    <div class="alert alert-@{{type}} mb-0 text-center border-secondary border-top m-1">
        @{{{message}}}
    </div>
</script>

@auth
    @php($user = Auth::user())
    @if(!$user->legal_agreed)
        <div class="modal fade" id="legal_modal" tabindex="-1" role="dialog"
             aria-labelledby="legalModalLabel" aria-hidden="true"
             data-keyboard="false" data-backdrop="static">
            <div class="modal-dialog modal-md vertical-align-center">
                <div class="modal-content">
                    <div class="probootstrap-modal-flex">
                        <div class="probootstrap-modal-content">
                            <div class="form-group">
                                {!! sprintf(__('Welcome back! In order to proceed, you have to agree to our %s, %s and %s.'),
                                 '<a href="' . route('legal.terms') . '">terms of service</a>',
                                 '<a href="' . route('legal.privacy') . '">privacy policy</a>',
                                 '<a href="' . route('legal.cookies') . '">cookie policy</a>')
                                 !!}
                            </div>
                            <div id="legal_confirm_btn" class="btn btn-primary">
                                {{ __('I agree') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endauth

@guest
    <!-- Modal login -->
    <div class="modal fade" id="login_modal" tabindex="-1" role="dialog"
         aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md vertical-align-center">
            <div class="modal-content">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                    <i class="fas fa-times"></i>
                </button>
                <div class="probootstrap-modal-flex">
                    <div class="probootstrap-modal-content">
                        @include('common.forms.login', ['modal' => true])
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END modal login -->

    <!-- Modal signup -->
    <div class="modal fade" id="register_modal" tabindex="-1" role="dialog"
         aria-labelledby="signupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md vertical-align-center">
            <div class="modal-content">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                    <i class="fas fa-times"></i>
                </button>
                <div class="probootstrap-modal-flex">
                    <div class="probootstrap-modal-content">
                        @include('common.forms.register', ['modal' => true])
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END modal signup -->
@endguest

<!-- Scripts -->
<script src="{{ asset('js/app.js') }}"></script>

@if (config('app.env') === 'production')
    <?php // Compiled only in production, otherwise include all files as-is to prevent having to recompile everything all the time ?>
    <script src="{{ asset('js/custom.js') }}"></script>

@else
    <?php // Only used on the home page ?>
    <script src="{{ asset('js/custom/home.js') }}"></script>

    <script src="{{ asset('js/custom/constants.js') }}"></script>
    <?php // Include in proper order ?>
    <script src="{{ asset('js/custom/util.js') }}"></script>
    <script src="{{ asset('js/custom/signalable.js') }}"></script>
    <script src="{{ asset('js/custom/dungeonmap.js') }}"></script>

    <script src="{{ asset('js/custom/enemyvisuals/enemyvisual.js') }}"></script>
    <script src="{{ asset('js/custom/enemyvisuals/enemyvisualicon.js') }}"></script>
    <script src="{{ asset('js/custom/enemyvisuals/enemyvisualmain.js') }}"></script>
    <script src="{{ asset('js/custom/enemyvisuals/enemyvisualmainaggressiveness.js') }}"></script>
    <script src="{{ asset('js/custom/enemyvisuals/enemyvisualmainenemyforces.js') }}"></script>
    <script src="{{ asset('js/custom/enemyvisuals/modifiers/modifier.js') }}"></script>
    <script src="{{ asset('js/custom/enemyvisuals/modifiers/modifierraidmarker.js') }}"></script>
    <script src="{{ asset('js/custom/enemyvisuals/modifiers/modifierinfested.js') }}"></script>
    <script src="{{ asset('js/custom/enemyvisuals/modifiers/modifierinfestedvote.js') }}"></script>

    <script src="{{ asset('js/custom/mapobject.js') }}"></script>
    <script src="{{ asset('js/custom/enemy.js') }}"></script>
    <script src="{{ asset('js/custom/enemypatrol.js') }}"></script>
    <script src="{{ asset('js/custom/enemypack.js') }}"></script>
    <script src="{{ asset('js/custom/route.js') }}"></script>
    <script src="{{ asset('js/custom/killzone.js') }}"></script>
    <script src="{{ asset('js/custom/mapcomment.js') }}"></script>
    <script src="{{ asset('js/custom/dungeonstartmarker.js') }}"></script>
    <script src="{{ asset('js/custom/dungeonfloorswitchmarker.js') }}"></script>
    <script src="{{ asset('js/custom/hotkeys.js') }}"></script>

    <script src="{{ asset('js/custom/mapcontrol.js') }}"></script>
    <script src="{{ asset('js/custom/mapcontrols/mapobjectgroupcontrols.js') }}"></script>
    <script src="{{ asset('js/custom/mapcontrols/drawcontrols.js') }}"></script>
    <script src="{{ asset('js/custom/mapcontrols/enemyforcescontrols.js') }}"></script>
    <script src="{{ asset('js/custom/mapcontrols/enemyvisualcontrols.js') }}"></script>
    <script src="{{ asset('js/custom/mapcontrols/factiondisplaycontrols.js') }}"></script>

    <script src="{{ asset('js/custom/admin/enemyattaching.js') }}"></script>
    <script src="{{ asset('js/custom/admin/admindungeonmap.js') }}"></script>
    <script src="{{ asset('js/custom/admin/adminenemy.js') }}"></script>
    <script src="{{ asset('js/custom/admin/adminenemypatrol.js') }}"></script>
    <script src="{{ asset('js/custom/admin/adminenemypack.js') }}"></script>
    <script src="{{ asset('js/custom/admin/admindrawcontrols.js') }}"></script>
    <script src="{{ asset('js/custom/admin/admindungeonstartmarker.js') }}"></script>
    <script src="{{ asset('js/custom/admin/admindungeonfloorswitchmarker.js') }}"></script>
    <script src="{{ asset('js/custom/admin/adminmapcomment.js') }}"></script>
    <?php // Include the rest ?>

    <script src="{{ asset('js/custom/groupcomposition.js') }}"></script>
    <script src="{{ asset('js/custom/mapobjectgroup.js') }}"></script>
    <script src="{{ asset('js/custom/mapobjectgroups/enemymapobjectgroup.js') }}"></script>
    <script src="{{ asset('js/custom/mapobjectgroups/enemypatrolmapobjectgroup.js') }}"></script>
    <script src="{{ asset('js/custom/mapobjectgroups/enemypackmapobjectgroup.js') }}"></script>
    <script src="{{ asset('js/custom/mapobjectgroups/routemapobjectgroup.js') }}"></script>
    <script src="{{ asset('js/custom/mapobjectgroups/killzonemapobjectgroup.js') }}"></script>
    <script src="{{ asset('js/custom/mapobjectgroups/mapcommentmapobjectgroup.js') }}"></script>
    <script src="{{ asset('js/custom/mapobjectgroups/dungeonstartmarkermapobjectgroup.js') }}"></script>
    <script src="{{ asset('js/custom/mapobjectgroups/dungeonfloorswitchmarkermapobjectgroup.js') }}"></script>

@endif
@yield('scripts')
</body>
</html>
