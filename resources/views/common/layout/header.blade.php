<?php

use App\Features\Heatmap;
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

$navs = [];

if ($currentUserGameVersion->key === GameVersion::GAME_VERSION_RETAIL) {
    if ($nextSeason !== null) {
        $navs[route('dungeonroutes.season', ['expansion' => $nextSeason->expansion, 'season' => $nextSeason->index])] = [
            'text' => $nextSeason->expansion_id !== $currentSeason->expansion_id ? $nextSeason->name_long : $nextSeason->name,
        ];
    }

    $navs[route('dungeonroutes.season', ['expansion' => $currentSeason->expansion, 'season' => $currentSeason->index])] = [
        'fa'   => 'fa fa-route',
        'text' => __('view_common.layout.header.browse_routes'),
    ];
} else {
    $navs[route('dungeonroutes.expansion', ['expansion' => $currentUserGameVersion->expansion])] = [
        'fa'   => 'fa fa-route',
        'text' => __('view_common.layout.header.browse_routes'),
    ];
}

$expansionRoutes = [];
foreach ($activeExpansions as $expansion) {
    $expansionRoutes[route('dungeonroutes.expansion', ['expansion' => $expansion])] =
        sprintf('<img src="%s" alt="%s" style="width: 50px"/> %s',
            ksgAssetImage(sprintf('expansions/%s.png', $expansion->shortname)),
            __($expansion->name),
//            $expansion->hasTimewalkingEvent() ?
//                __('view_common.layout.header.routes_timewalking', ['expansion' => __($expansion->name)]) :
            __('view_common.layout.header.routes', ['expansion' => __($expansion->name)])
        );
}

$navs[__('view_common.layout.header.browse_by_expansion')] = [
    'fa'    => 'fas fa-stream',
    'items' => $expansionRoutes,
];

if (Feature::active(Heatmap::class) && $currentUserGameVersion->key === GameVersion::GAME_VERSION_RETAIL) {
    $navs[route('dungeon.heatmaps.list')] = [
        'fa'   => 'fas fa-fire text-danger',
        'text' => __('view_common.layout.header.heatmaps'),
        'new'  => true
    ];
}

$navs[route('dungeon.explore.gameversion.list', ['gameVersion' => $currentUserGameVersion])] = [
    'fa'   => 'fas fa-compass',
    'text' => __('view_common.layout.header.explore'),
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
    <div class="container p-0">
        <a class="navbar-brand" href="/">
            <img src="{{ ksgAssetImage('logo/logo_and_text.png') }}" alt="{{ config('app.name') }}"
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
                    @elseif(isset($opts['items']))
                            <?php
                            /** @noinspection PhpUndefinedVariableInspection */
                            $headerText = $route;
                            $dropdownId = Str::slug($headerText)
                            ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="{{ $dropdownId }}" role="button"
                               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                @isset($opts['fa'])
                                    <i class="{{ $opts['fa'] }}"></i>
                                @endisset
                                {{ $headerText }}
                            </a>
                            <div class="dropdown-menu text-center text-xl-left" aria-labelledby="{{ $dropdownId }}">
                                @foreach($opts['items'] as $itemKey => $item)
                                    <a class="dropdown-item" href="{{ $itemKey }}">{!! $item !!}</a>
                                @endforeach
                            </div>
                        </li>
                    @else
                        <li class="nav-item">
                                <?php
                                // Check if the route that we're currently on is the same as the route in the nav
                                // If so, show it as active
                                $active    = '';
                                $parsedUrl = (parse_url($route));
                                if (is_array($parsedUrl)) {
                                    $routePath = trim($parsedUrl['path'], '/');
                                    if (Str::startsWith($routePath, Request::path())) {
                                        $active = 'active';
                                    }
                                }
                                ?>
                            <a class="nav-link pr-3 {{ $active }}"
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
                    @endif
                @endforeach
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item nav-item-divider"></li>
                <li class="nav-item">
                    <a class="nav-link pr-3 {{ str_starts_with(Request::url(), route('dungeonroutes.search')) ? 'active' : '' }}"
                       href="{{ route('dungeonroutes.search') }}">
                        <i class="fas fa-search"></i>
                    </a>
                </li>
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
