<?php
$numUserReports = \App\Models\UserReport::where('handled', 0)->count();

$user = \Illuminate\Support\Facades\Auth::user();
// Show the legal modal or not if people didn't agree to it yet
$showLegalModal = isset($showLegalModal) ? $showLegalModal : true;
// Custom content or not
$custom = isset($custom) ? $custom : false;
// Wide mode or not (only relevant if custom = false)
$wide = isset($wide) ? $wide : false;
// Show header or not
$header = isset($header) ? $header : true;
// Show footer or not
$footer = isset($footer) ? $footer : true;
// Setup the title
$title = isset($title) ? $title . ' - ' : '';
// Any additional parameters to pass to the login/register blade
$loginParams = isset($loginParams) ? $loginParams : [];
$registerParams = isset($registerParams) ? $registerParams : [];
// Show cookie consent
$cookieConsent = isset($cookieConsent) ? $cookieConsent : true;
// If user already approved of the cookie..
if (isset($_COOKIE['cookieconsent_status']) && $_COOKIE['cookieconsent_status'] === 'dismiss') {
    // Don't bother the user with it anymore
    $cookieConsent = false;
}
// Easy switch
$isProduction = config('app.env') === 'production';
// Show ads if not set
$showAds = isset($showAds) ? $showAds : true;
// If we should show ads, are logged in, user has paid for no ads, or we're not in production..
if (($showAds && Auth::check() && $user->hasPaidTier('ad-free')) || !$isProduction) {
    // No ads
    $showAds = false;
}
// Analytics or not, default = $isProduction
$analytics = isset($analytics) ? $analytics : $isProduction;
// Current Git version
$version = \Tremby\LaravelGitVersion\GitVersionHelper::getVersion();

$newChangelog = isset($_COOKIE['changelog_release']) ? \App\Models\Release::max('id') > (int)$_COOKIE['changelog_release'] : true;
$newToTeams = isset($_COOKIE['viewed_teams']) ? $_COOKIE['viewed_teams'] === 1 : true;
?><!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title . config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
    <link href="{{ asset('css/app-' . $version . '.css') }}" rel="stylesheet">
    <link href="{{ asset('css/custom-' . $version . '.css') }}" rel="stylesheet">
    <link href="{{ asset('css/lib-' . $version . '.css') }}" rel="stylesheet">
    <link rel="icon" href="/images/icon/favicon.ico">
    @yield('head')

    @include('common.general.inlinemanager')
    @include('common.general.inline', ['path' => 'layouts/app', 'section' => false, 'options' => ['guest' => Auth::guest()]])
    @include('common.general.sitescripts', ['showLegalModal' => $showLegalModal])

    @if($cookieConsent)
        @include('common.thirdparty.cookieconsent')
    @endif

    @if($showAds)
        @include('common.thirdparty.adsense')
    @endif
    @if($analytics)
        @include('common.thirdparty.analytics')
    @endif
</head>
<body>
<div id="app">
    @if($header)
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand" href="/">{{ config('app.name', 'Laravel') }}</a>
                <button class="navbar-toggler" type="button" data-toggle="collapse"
                        data-target="#navbarSupportedContent"
                        aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse text-center text-lg-left" id="navbarSupportedContent">
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('dungeonroutes') }}">{{ __('Routes') }}</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="demo_dropdown" role="button"
                               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ __('Demo') }}
                            </a>

                            <div class="dropdown-menu text-center text-lg-left" aria-labelledby="demo_dropdown">
                                @foreach(\App\Models\DungeonRoute::where('demo', true)->where('published', true)->get() as $route)
                                    <a class="dropdown-item test-dropdown-menu"
                                       href="{{ route('dungeonroute.view', ['dungeonroute' => $route->public_key]) }}">
                                        {{ $route->dungeon->name }}
                                    </a>
                                @endforeach
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('misc.affixes') }}">{{ __('Affixes') }}</a>
                        </li>
                        @if (Auth::check())
                            <li class="nav-item">
                                <a class="nav-link"
                                   href="{{ route('team.list') }}">{{ __('Teams') }}
                                    @if($newToTeams)
                                        <sup class="text-success">{{ __('NEW') }}</sup>
                                    @endif
                                </a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('misc.changelog') }}">
                                {{ __('Changelog') }}
                                @if($newChangelog)
                                    <sup class="text-success">{{ __('NEW') }}</sup>
                                @endif
                            </a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item mr-lg-2">
                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle col-lg-auto" type="button"
                                        id="tryImportMDTDropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">
                                    {{__('Try it!')}}
                                </button>
                                <div class="dropdown-menu text-center text-lg-left"
                                     aria-labelledby="newRouteDropdownMenuButton">
                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#try_modal">
                                        {{__('New route')}}
                                    </a>
                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#try_mdt_import_modal">
                                        {{__('Import from MDT')}}
                                    </a>
                                </div>
                            </div>
                        </li>
                        @if (Auth::guest())
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-toggle="modal" data-target="#login_modal">
                                    {{__('Login')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-toggle="modal" data-target="#register_modal">
                                    {{__('Register')}}
                                </a>
                            </li>
                        @else
                            <li class="nav-item mr-lg-2 mt-1 mt-lg-0">
                                <div class="dropdown">
                                    <button class="btn btn-success dropdown-toggle col-lg-auto" type="button"
                                            id="newRouteDropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                            aria-expanded="false">
                                        <i class="fas fa-plus"></i> {{__('Create route')}}
                                    </button>
                                    <div class="dropdown-menu text-center text-lg-left"
                                         aria-labelledby="newRouteDropdownMenuButton">
                                        <a class="dropdown-item" href="{{ route('dungeonroute.new') }}">
                                            {{ __('New route') }}
                                        </a>
                                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#mdt_import_modal">
                                            {{__('Import from MDT')}}
                                        </a>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                   data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-user"></i> {{ $user->name }}
                                </a>
                                <div class="dropdown-menu text-center text-lg-left" aria-labelledby="navbarDropdown">
                                    @if( $user->hasRole('admin'))
                                        <a class="dropdown-item"
                                           href="{{ route('dashboard.home') }}">{{__('Admin dashboard')}}</a>
                                        <a class="dropdown-item"
                                           href="{{ route('tracker.stats.index') }}">{{__('Admin stats')}}</a>
                                        <a class="dropdown-item"
                                           href="{{ route('admin.tools') }}">{{__('Admin tools')}}</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item"
                                           href="{{ route('admin.releases') }}">{{__('View releases')}}</a>
                                        @if( $user->isAbleTo('read-expansions') )
                                            <a class="dropdown-item"
                                               href="{{ route('admin.expansions') }}">{{__('View expansions')}}</a>
                                        @endif
                                        @if( $user->isAbleTo('read-dungeons') )
                                            <a class="dropdown-item"
                                               href="{{ route('admin.dungeons') }}">{{__('View dungeons')}}</a>
                                        @endif
                                        @if( $user->isAbleTo('read-npcs') )
                                            <a class="dropdown-item"
                                               href="{{ route('admin.npcs') }}">{{__('View NPCs')}}</a>
                                        @endif
                                        <a class="dropdown-item"
                                           href="{{ route('admin.users') }}">{{__('View users')}}</a>
                                        <a class="dropdown-item"
                                           href="{{ route('admin.userreports') }}">{{__('View user reports') }}
                                            <span class="badge badge-primary badge-pill">{{ $numUserReports }}</span>
                                        </a>
                                        <div class="dropdown-divider"></div>
                                    @endif
                                    <a class="dropdown-item"
                                       href="{{ route('profile.edit') }}">{{ __('My profile') }}</a>
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
    @endif

    @if($custom)
        @yield('content')

    @elseif(isset($menuItems))
        <div class="container container_wide mt-3">
            <div class="row">
                <div class="col-xl-3 bg-secondary p-3">
                    <h4>{{ $menuTitle }}</h4>
                    <hr>
                    @isset($menuModels)
                        <select id="selected_model_id" class="form-control selectpicker">
                            @foreach($menuModels as $menuModel)
                                @php($hasIcon = isset($menuModel->iconfile))
                                <option
                                        data-url="{{ route($menuModelsRoute, ['id' => $menuModel->id]) }}"
                                        @if($hasIcon)
                                        data-content="<img src='{{ url('storage/' . $menuModel->iconfile->getUrl()) }}' style='max-height: 16px;'/> {{ $menuModel->name }}"
                                        @endif
                                        {{ $model->id === $menuModel->id ? 'selected' : '' }}
                                >{{ $hasIcon ? '' : $menuModel->name }}</option>
                            @endforeach
                        </select>
                        <hr>
                    @endisset
                    <ul class="nav flex-column nav-pills">
                        @foreach($menuItems as $index => $menuItem)
                            <li class="nav-item">
                                <a class="nav-link {{ $index === 0 ? 'active' : '' }}" id="routes-tab" data-toggle="tab"
                                   href="{{ $menuItem['target'] }}" role="tab"
                                   aria-controls="routes" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                    <i class="fas {{ $menuItem['icon'] }}"></i> {{ $menuItem['text'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="col-xl-9 bg-secondary ml-0 mt-xl-0 mt-3 p-3">
                    @yield('content')
                </div>
            </div>
        </div>

    @else

        @if (!$isProduction && (!Auth::check() || !$user->hasRole('admin')))
            <div class="container-fluid alert alert-warning text-center mt-4">
                <i class="fa fa-exclamation-triangle"></i>
                {{ __('Warning! You are currently on the staging environment of Keystone.guru. This is NOT the main site.') }}
                <br>
                <a href="https://keystone.guru/">{{ __('Take me to the main site!') }}</a>
            </div>
        @endif

        @yield('global-message')

        @if( $showAds )
            <div align="center" class="mt-4">
                @include('common.thirdparty.adunit', ['type' => 'header'])
            </div>
        @endif

        <div class="container-fluid">
            <div class="row">
                <div class="{{ $wide ? "flex-fill ml-lg-3 mr-lg-3" : "col-md-8 offset-md-2" }}">
                    <div class="card mt-3 mb-3">
                        <div class="card-header {{ $wide ? "panel-heading-wide" : "" }}">
                            <div class="row">
                                @hasSection('header-addition')
                                    <div class="col text-center">
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
                            @include('common.general.messages')

                            @yield('content')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <header class="fixed-top">
        <div class="row">
            <div id="fixed_header_container" class="col-6 m-auto">
            </div>
        </div>
    </header>

    <footer class="fixed-bottom">
        <div class="row">
            <div id="fixed_footer_container" class="col-6 m-auto">
            </div>
        </div>
    </footer>

    @if( $footer )

        @if( $showAds )
            <div align="center" class="mt-4">
                @include('common.thirdparty.adunit', ['type' => 'footer'])
            </div>
        @endif

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
                    <a class="nav-item nav-link" href="/">
                        ©{{ date('Y') }} {{ \Tremby\LaravelGitVersion\GitVersionHelper::getNameAndVersion() }}
                    </a>
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

@auth
    @if(!$user->legal_agreed)
@section('modal-content')
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
@overwrite
@include('common.general.modal', ['id' => 'legal_modal'])
@endif
@endauth

<!-- Modal try -->
@section('modal-content')
    @include('common.forms.try', ['modal' => true])
@overwrite
@include('common.general.modal', ['id' => 'try_modal'])
<!-- END modal try -->

<!-- Modal MDT import -->
@section('modal-content')
    {{ Form::open(['route' => 'dungeonroute.new.mdtimport']) }}
    <h3>
        {{ __('Import from MDT string') }}
    </h3>
    <div class="form-group">
        {!! Form::label('import_string', __('Paste your Method Dungeon Tools export string')) !!}
        {{ Form::textarea('import_string_textarea', '', ['class' => 'form-control import_mdt_string_textarea']) }}
        {{ Form::hidden('import_string', '', ['class' => 'import_string']) }}
    </div>
    <div class="form-group">
        <div class="bg-info p-1 import_mdt_string_loader" style="display: none;">
            <?php /* I'm Dutch, of course the loading indicator is a stroopwafel */ ?>
            <i class="fas fa-stroopwafel fa-spin"></i> {{ __('Parsing your string...') }}
        </div>
    </div>
    <div class="form-group">
        <div class="import_mdt_string_details">

        </div>
    </div>
    <div class="form-group">
        <div class="import_mdt_string_warnings">

        </div>
    </div>
    <div class="form-group">
        {!! Form::hidden('try', 0, ['class' => 'hidden_try']) !!}
        {!! Form::submit(__('Import'), ['class' => 'btn btn-primary col-md-auto', 'disabled']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@overwrite
@include('common.general.modal', ['id' => 'mdt_import_modal'])
@include('common.general.modal', ['id' => 'try_mdt_import_modal'])
<!-- END modal MDT import -->

@guest
    <!-- Modal login -->
@section('modal-content')
    @include('common.forms.login', array_merge(['modal' => true], $loginParams))
@overwrite
@include('common.general.modal', ['id' => 'login_modal', 'class' => 'login-modal-dialog'])
<!-- END modal login -->

<!-- Modal register -->
@section('modal-content')
    @include('common.forms.register', array_merge(['modal' => true], $registerParams))
@overwrite
@include('common.general.modal', ['id' => 'register_modal', 'class' => 'register-modal-dialog'])
<!-- END modal register -->
@endguest

<!-- Scripts -->
<script src="{{ asset('js/app-' . $version . '.js') }}"></script>
<?php // Compiled only in production, otherwise include all files as-is to prevent having to recompile everything all the time ?>
<script src="{{ asset('js/custom-' . $version . '.js') }}"></script>
<script src="{{ asset('js/lib-' . $version . '.js') }}"></script>
@yield('scripts')
</body>
</html>
