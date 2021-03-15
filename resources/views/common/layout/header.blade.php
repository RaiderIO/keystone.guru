<?php
/** @var $hasNewChangelog boolean */
$navs = [
    route('dungeonroutes') => [
        'text' => __('Routes')
    ],
    route('misc.affixes') => [
        'text' => __('Affixes')
    ],
    route('misc.changelog') => [
        'text' => __('Changelog'),
        'new' => $hasNewChangelog
    ],
];

?>
<div class="navbar-top-fixed-spacer"></div>
<nav
    class="navbar fixed-top navbar-expand-lg {{ $theme === 'lux' ? 'navbar-light' : 'navbar-dark' }} bg-header"
    data-toggle="navbar-shrink">
    <div class="container">
        <a class="navbar-brand" href="/">
            <img src="{{ url('/images/logo/logo_and_text.png') }}" alt="{{ config('app.name', 'Laravel') }}"
                 height="44px;" width="200px;">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse"
                data-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false"
                aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse text-center text-lg-left" id="mainNavbar">
            <ul class="navbar-nav mr-auto">
                @foreach($navs as $route => $opts)
                    <li class="nav-item">
                        <a class="nav-link  {{ strpos(Request::url(), $route) === 0 ? 'active' : '' }}" href="{{ $route }}">
                            {{ $opts['text'] }}
                            @if(isset($opts['new']) && $opts['new'])
                                <sup class="text-success">{{ __('NEW') }}</sup>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item mr-2">
                    <a class="nav-link" href="{{ route('dungeonroutes.search') }}">
                        <i class="fas fa-search"></i> {{__('Search')}}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-accent" href="#"
                       data-toggle="modal" data-target="#create_route_modal">
                        <i class="fas fa-plus"></i> {{__('Create route')}}
                    </a>
                </li>
                <li class="nav-item nav-item-divider"></li>
                @include('common.layout.navuser')
                @include('common.layout.navthemeswitch')
            </ul>
        </div>
    </div>
</nav>