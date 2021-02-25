<?php
/** @var $theme string */
/** @var $hasNewChangelog boolean */

$isDarkMode = $theme === 'darkly';
?>
<div class="navbar-top-fixed-spacer"></div>
<nav
    class="navbar fixed-top navbar-expand-lg navbar-dark bg-header"
    data-toggle="navbar-shrink">
    <div class="container">
        <a class="navbar-brand" href="/">
            <img src="{{ url('/images/logo/logo_and_text.png') }}" alt="{{ config('app.name', 'Laravel') }}"
                 height="44px;" width="200px;">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse"
                data-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse text-center text-lg-left" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('dungeonroutes') }}">{{ __('Routes') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('misc.affixes') }}">{{ __('Affixes') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('misc.changelog') }}">
                        {{ __('Changelog') }}
                        @if($hasNewChangelog)
                            <sup class="text-success">{{ __('NEW') }}</sup>
                        @endif
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item mr-lg-2">
                    <a class="btn btn-accent" href="#"
                       data-toggle="modal" data-target="#create_route_modal">
                        <i class="fas fa-plus"></i> {{__('Create route')}}
                    </a>
                </li>
                @if (Auth::guest())
                    <li class="nav-item">
                        <a class="btn btn-info" href="#" data-toggle="modal" data-target="#login_modal">
                            <i class="fas fa-sign-in-alt"></i> {{__('Login')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-toggle="modal" data-target="#register_modal">
                            {{__('Register')}}
                        </a>
                    </li>
                @else
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user"></i> {{ $user->name }}
                        </a>
                        <div class="dropdown-menu text-center text-lg-left" aria-labelledby="navbarDropdown">
                            @if( $user->hasRole('admin'))
                                <a class="dropdown-item"
                                   href="{{ route('dashboard.home') }}">{{__('Admin dashboard')}}</a>
                                @if( env('TRACKER_ENABLED'))
                                    <a class="dropdown-item"
                                       href="{{ route('tracker.stats.index') }}">{{__('Admin stats')}}</a>
                                @endif
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
                                   href="{{ route('admin.spells') }}">{{__('View spells')}}</a>
                                <a class="dropdown-item"
                                   href="{{ route('admin.users') }}">{{__('View users')}}</a>
                                <a class="dropdown-item"
                                   href="{{ route('admin.userreports') }}">{{__('View user reports') }}
                                    @if($numUserReports > 0)
                                        <span
                                            class="badge badge-primary badge-pill">{{ $numUserReports }}</span>
                                    @endif
                                </a>
                                <div class="dropdown-divider"></div>
                            @endif
                            <a class="dropdown-item" href="{{ route('profile.routes') }}">{{ __('My routes') }} </a>
                            <a class="dropdown-item" href="{{ route('profile.tags') }}">{{ __('My tags') }} </a>
                            <a class="dropdown-item" href="{{ route('team.list') }}">{{ __('My teams') }} </a>
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
                <li>
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                        <label class="btn btn-dark {{ $isDarkMode ? '' : 'active' }}">
                            <input type="radio" id="theme_light_mode" class="theme_switch_btn" autocomplete="off" data-theme="superhero" {{ $isDarkMode ? '' : 'checked' }}>
                            <i class="fas fa-sun"></i>
                        </label>
                        <label class="btn btn-dark {{ $isDarkMode ? 'active' : '' }}">
                            <input type="radio" id="theme_dark_mode" class="theme_switch_btn" autocomplete="off" data-theme="darkly" {{ $isDarkMode ? 'checked' : '' }}>
                            <i class="fas fa-moon"></i>
                        </label>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>