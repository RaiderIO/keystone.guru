<?php

use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @var GameVersion             $currentUserGameVersion
 * @var Collection<GameVersion> $allGameVersions
 * @var Collection<Expansion>   $activeExpansions
 * @var Season                  $currentSeason
 * @var Season                  $nextSeason
 */

$navs = [
    route('dungeonroutes.search') => [
        'fa'   => 'fas fa-search',
        'text' => __('view_common.layout.header.search'),
    ],
    route('dungeon.explore.list') => [
        'fa'   => 'fas fa-compass',
        'text' => __('view_common.layout.header.explore'),
    ],
];

$expansionRoutes = [];
foreach ($activeExpansions as $expansion) {
    $expansionRoutes[route('dungeonroutes.expansion', ['expansion' => $expansion])] =
        sprintf('<img src="%s" alt="%s" style="width: 50px"/> %s',
            url(sprintf('images/expansions/%s.png', $expansion->shortname),
            ),
            __($expansion->name),
//            $expansion->hasTimewalkingEvent() ?
//                __('view_common.layout.header.routes_timewalking', ['expansion' => __($expansion->name)]) :
            __('view_common.layout.header.routes', ['expansion' => __($expansion->name)])
        );
}

if ($currentUserGameVersion->key === GameVersion::GAME_VERSION_RETAIL) {
    if ($nextSeason !== null) {
        $navs[route('dungeonroutes.season', ['expansion' => $nextSeason->expansion, 'season' => $nextSeason->index])] = [
            'text' => $nextSeason->expansion_id !== $currentSeason->expansion_id ? $nextSeason->name_long : $nextSeason->name,
        ];
    }

    $navs[route('dungeonroutes.season', ['expansion' => $currentSeason->expansion, 'season' => $currentSeason->index])] = [
        'text' => $currentSeason->name,
    ];
}

$navs[__('view_common.layout.header.expansion_routes')] = $expansionRoutes;

$navs[route('misc.affixes')] = [
    'text' => __('view_common.layout.header.affixes'),
];

?>
<div
    class="game_version_header navbar-first d-none d-lg-block fixed-top bg-dark {{ $theme === User::THEME_LUX ? 'navbar-light' : 'navbar-dark' }}">
    <div class="container">
        <div class="row">
            @foreach ($allGameVersions as $gameVersion)
                @php($isSelectedGameVersion = $currentUserGameVersion->id === $gameVersion->id)
                <div class="game_version col-auto px-2 m-1  {{ $isSelectedGameVersion ? 'bg-primary' : '' }}">
                    <a class="{{ $isSelectedGameVersion ? 'active' : '' }}"
                       href="{{ route('gameversion.update', ['gameVersion' => $gameVersion]) }}">
                        @include('common.gameversion.gameversionheader', [
                            'gameVersion' => $gameVersion,
                            'iconType' => $isSelectedGameVersion ? 'black' : 'white',
                        ])
                    </a>
                </div>
            @endforeach
            <div class="col">
                &nbsp;
            </div>
        </div>
    </div>
</div>
<div class="navbar-top-fixed-spacer"></div>
<nav
    class="navbar navbar-second fixed-top navbar-expand-lg {{ $theme === User::THEME_LUX ? 'navbar-light' : 'navbar-dark' }} bg-header"
    data-toggle="navbar-shrink">
    <div class="container">
        <a class="navbar-brand" href="/">
            <img src="{{ url('/images/logo/logo_and_text.png') }}" alt="{{ config('app.name') }}"
                 height="44px;" width="200px;">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse"
                data-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false"
                aria-label="{{ __('view_common.layout.header.toggle_navigation_title') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse text-center text-lg-left" id="mainNavbar">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item px-3">
                    <a class="btn btn-accent" href="#"
                       data-toggle="modal" data-target="#create_route_modal">
                        <i class="fas fa-plus"></i> {{__('view_common.layout.header.create_route')}}
                    </a>
                </li>
                @foreach($navs as $route => $opts)
                    @if($opts === 'divider')
                        <li class="nav-item nav-item-divider"></li>
                    @elseif(filter_var($route, FILTER_VALIDATE_URL) !== false)
                        <li class="nav-item">
                            <a class="nav-link pr-3 {{ str_starts_with(Request::url(), $route) ? 'active' : '' }}"
                               href="{{ $route }}">
                                @isset($opts['fa'])
                                    <i class="{{ $opts['fa'] }}"></i>
                                @endisset
                                {{ $opts['text'] }}
                                @if(isset($opts['new']) && $opts['new'])
                                    <sup class="text-success">{{ __('view_common.layout.header.new') }}</sup>
                                @endif
                            </a>
                        </li>
                    @else
                            <?php
                            /** @noinspection PhpUndefinedVariableInspection */
                            $headerText = $route;
                            $dropdownId = Str::slug($headerText)
                            ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="{{ $dropdownId }}" role="button"
                               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ $headerText }}
                            </a>
                            <div class="dropdown-menu text-center text-xl-left" aria-labelledby="{{ $dropdownId }}">
                                @foreach($opts as $optsKey => $text)
                                    <a class="dropdown-item" href="{{ $optsKey }}">{!! $text !!}</a>
                                @endforeach
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item nav-item-divider"></li>
                @include('common.layout.nav.gameversions')
                @include('vendor.language.flags')
                @include('common.layout.nav.user')
                @include('common.layout.nav.themeswitch')
                {{--                <li class="nav-item nav-item-divider"></li>--}}
                {{--                @include('common.layout.nav.uploadlogs')--}}
            </ul>
        </div>
    </div>
</nav>
