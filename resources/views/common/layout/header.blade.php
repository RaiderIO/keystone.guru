<?php

use App\Features\Heatmap;
use App\Features\NpcCompendium;
use App\Features\SearchPageRework;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @var GameVersion                  $currentUserGameVersion
 * @var Collection<int, GameVersion> $allGameVersions
 * @var Collection<int, Expansion>   $activeExpansions
 * @var Collection<int, Dungeon>     $gameVersionDungeons
 * @var Season                       $currentSeason
 * @var Season|null                  $nextSeason
 * @var bool                         $forceShrink
 * @var bool                         $showMore
 * @var bool                         $showDungeonContext
 * @var Collection<string, string>   $dungeonContextLinks
 */

$navs                = [];
$showMore            ??= false;
$showDungeonContext  ??= true;
$forceShrink         ??= false;
$dungeonContextLinks ??= null;

if ($currentUserGameVersion->key === GameVersion::GAME_VERSION_RETAIL) {
    if ($nextSeason !== null) {
        if ($nextSeason->expansion_id !== $currentSeason->expansion_id) {
            $navs[route('dungeonroutes.expansion.season', [
                'expansion' => $nextSeason->expansion,
                'season'    => $nextSeason->index,
            ])] = [
                'text' => $nextSeason->name_long,
            ];
        } else {
            $navs[route('dungeonroutes.season', [
                'gameVersion' => $currentUserGameVersion,
                'season'      => $nextSeason->index,
            ])] = [
                'text' => $nextSeason->name,
            ];
        }
    }

    $navs[route('dungeonroutes.gameVersion', ['gameVersion' => $currentUserGameVersion,])] = [
        'fa'   => 'fa fa-route',
        'text' => __('view_common.layout.header.browse_routes'),
    ];
} else {
    $navs[route('dungeonroutes.gameVersion', ['gameVersion' => $currentUserGameVersion])] = [
        'fa'   => 'fa fa-route',
        'text' => __('view_common.layout.header.browse_routes'),
    ];
}

$expansionRoutes = [];
foreach ($activeExpansions as $expansion) {
    $expansionRoutes[route('dungeonroutes.expansion', ['expansion' => $expansion])] =
        sprintf('<img src="%s" alt="%s" style="width: 50px"/> %s',
            $expansion->getIconUrl(),
            __($expansion->name),
            __('view_common.layout.header.routes', ['expansion' => __($expansion->name)])
        );
}

if (Feature::active(Heatmap::class) && $currentUserGameVersion->key === GameVersion::GAME_VERSION_RETAIL) {
    $navs[route('dungeon.heatmap.gameversion', ['gameVersion' => $currentUserGameVersion])] = [
        'fa'   => 'fas fa-fire text-danger',
        'text' => __('view_common.layout.header.heatmaps'),
    ];
}

$isActiveRoute = function (string $route, bool $strict = false) {
    // Check if the route that we're currently on is the same as the route in the nav
    // If so, show it as active
    $active    = null;
    $parsedUrl = (parse_url((string)$route));
    if (is_array($parsedUrl)) {
        $routePath = trim($parsedUrl['path'], '/');
        if ($strict ? Request::path() === $routePath : str_starts_with(Request::path(), $routePath)) {
            $active = 'active';
        }
    }

    return $active;
};
?>
<div
    class="game_version_header navbar-first d-none d-lg-block fixed-top
    {{ $showDungeonContext ? 'has_dungeon_context_header' : '' }}
     {{ User::isThemeDark($theme) ? 'navbar-dark' : 'navbar-light' }}">
    <div class="container discover bg-dark rounded ">
        <div class="row">
            @foreach ($allGameVersions as $gameVersion)
                @include('common.gameversion.gameversionheader', [
                    'gameVersion' => $gameVersion,
                    'currentUserGameVersion' => $currentUserGameVersion,
                ])
            @endforeach
            <div class="col">
                &nbsp;
            </div>
        </div>
        @if($showDungeonContext)
            <div class="row no-gutters dungeon_context_header {{ $forceShrink ? 'navbar-shrink' : '' }}"
                 data-toggle="navbar-shrink" style="height: 99px;">
                <div class="col">
                    @include('common.dungeon.list', [
                        'gameVersion' => $currentUserGameVersion,
                        'dungeons' => $gameVersionDungeons,
                        'colCount' => $gameVersionDungeons->count(),
                        'useAbbreviation' => true,
                        'selectable' => true,
                        'showMore' => $showMore,
                        'selected' => Dungeon::getUserOrDefaultDungeon()->key,
                        'links' => $dungeonContextLinks ?? $gameVersionDungeons->mapWithKeys(fn (Dungeon $dungeon) => [
                                $dungeon->key => route('dungeon.changecontext', [
                                    'dungeon' => $dungeon,
                                ])
                            ]),
                    ])
                </div>
            </div>
        @endif
    </div>
</div>
@if(!$forceShrink)
    <div class="navbar-top-fixed-spacer" style="height: 190px;"></div>
@endif
<nav
    class="navbar navbar-second fixed-top navbar-expand-lg
     {{ $forceShrink ? 'navbar-shrink' : '' }}
     {{ User::isThemeDark($theme) ? 'navbar-dark' : 'navbar-light' }}"
    data-toggle="navbar-shrink">
    <div class="container px-1 bg-header rounded">
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
                    @else
                        <li class="nav-item">
                            <a class="nav-link pr-3 {{ $isActiveRoute($route) }}"
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

                @if(Feature::active(NpcCompendium::class))
                        <?php
                        $compendiumRoutes       = [
                            route('compendium.index')          => sprintf('%s %s', '<i class="fas fa-book-open"></i>', __('view_common.layout.header.compendium_overview')),
                            route('npc.compendium.index')      => sprintf('%s %s', '<i class="fas fa-dragon"></i>', __('view_common.layout.header.npc_compendium')),
                            route('spell.compendium.index')    => sprintf('%s %s', '<i class="fas fa-magic"></i>', __('view_common.layout.header.spell_compendium')),
                            route('compendium.activity.index') => sprintf('%s %s', '<i class="fas fa-stream"></i>', __('view_common.layout.header.compendium_activity')),
                            route('compendium.class.index')    => sprintf('%s %s', '<i class="fas fa-hat-wizard"></i>', __('view_common.layout.header.class_compendium')),
                        ];
                        $hasCompendiumSubActive = null;
                        $compendiumHeaderText   = __('view_common.layout.header.compendium');
                        $compendiumDropdownId   = Str::slug($compendiumHeaderText);
                        foreach ($compendiumRoutes as $itemKey => $item) {
                            $hasCompendiumSubActive ??= $isActiveRoute($itemKey, true);
                        }
                        ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ $hasCompendiumSubActive }}" href="#"
                           id="{{ $compendiumDropdownId }}" role="button"
                           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-book-open"></i>
                            {{ $compendiumHeaderText }}
                        </a>
                        <div class="dropdown-menu text-center text-xl-left"
                             aria-labelledby="{{ $compendiumDropdownId }}">
                            @foreach($compendiumRoutes as $itemKey => $item)
                                <a class="dropdown-item {{ $isActiveRoute($itemKey, true) }}"
                                   href="{{ $itemKey }}">{!! $item !!}</a>
                            @endforeach
                        </div>
                    </li>
                @endif
            </ul>
            <ul class="navbar-nav">
                <?php
                /** @noinspection PhpUndefinedVariableInspection */
                $hasSubItemActive = null;
                $headerText       = __('view_common.layout.header.browse_by_expansion');
                $dropdownId       = Str::slug($headerText);
                // Determine if any of the sub-items are active
                foreach ($expansionRoutes as $itemKey => $item) {
                    $hasSubItemActive ??= $isActiveRoute($itemKey);
                }
                ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ $hasSubItemActive }}" href="#" id="{{ $dropdownId }}"
                       role="button"
                       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-stream"></i>
                        {{ $headerText }}
                    </a>
                    <div class="dropdown-menu text-center text-xl-left" aria-labelledby="{{ $dropdownId }}">
                        @foreach($expansionRoutes as $itemKey => $item)
                            <a class="dropdown-item {{ $isActiveRoute($itemKey) }}"
                               href="{{ $itemKey }}">{!! $item !!}</a>
                        @endforeach
                    </div>
                </li>
                @php($route = route('dungeon.explore.gameversion', ['gameVersion' => $currentUserGameVersion]))
                <li class="nav-item">
                    <a class="nav-link pr-3 {{ $isActiveRoute($route) }}"
                       href="{{ $route }}">
                        <i class="fas fa-compass"></i> {{ __('view_common.layout.header.explore') }}
                    </a>
                </li>
                <li class="nav-item nav-item-divider"></li>
                <li class="nav-item">
                    @if(Feature::active(SearchPageRework::class))
                        <a class="nav-link pr-3 {{ str_starts_with(Request::url(), route('dungeon.dungeonroute.search')) ? 'active' : '' }}"
                           href="{{ route('dungeon.dungeonroute.search') }}">
                            <i class="fas fa-search"></i>
                        </a>
                    @else
                        <a class="nav-link pr-3 {{ str_starts_with(Request::url(), route('dungeonroutes.search')) ? 'active' : '' }}"
                           href="{{ route('dungeonroutes.search') }}">
                            <i class="fas fa-search"></i>
                        </a>
                    @endif
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
