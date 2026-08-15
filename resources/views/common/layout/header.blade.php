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
 * @var Season|null                  $dungeonContextNextSeason
 * @var string|null                  $dungeonContextNextSeasonLink
 * @var bool                         $forceShrink
 * @var bool                         $showMore
 * @var bool                         $showDungeonContext
 * @var bool                         $showGameVersionSelection
 * @var bool                         $showExpansionNav
 * @var Collection<string, string>   $dungeonContextLinks
 * @var string|false                 $headerId
 */

$navs                     = [];
$showMore                 ??= false;
$showDungeonContext       ??= true;
// Map pages hide the game version selection row - it made the floating header too bulky
$showGameVersionSelection ??= true;
$showExpansionNav         ??= true;
$forceShrink              ??= false;
$dungeonContextLinks      ??= null;
// The map view passes false (not null - ??= would overwrite null) - its own #map_header wraps
// this include, and a stray #site_header would make siteheader.js measure the wrong element.
$headerId                 ??= 'site_header';
// Defense in depth for #3806 - GlobalComposer normally supplies this, but view composers are
// skipped entirely on paths ViewService::shouldLoadViewVariables() blacklists (e.g. /ajax/),
// so an HTML error view rendered for one of those paths would otherwise crash here.
$currentUserGameVersion   ??= GameVersion::getUserOrDefaultGameVersion();

$navs[route('dungeonroutes.gameVersion', ['gameVersion' => $currentUserGameVersion])] = [
    'fa'   => 'fa fa-route',
    'text' => __('view_common.layout.header.browse_routes'),
];

$expansionRoutes = [];
if ($showExpansionNav) {
    foreach ($activeExpansions as $expansion) {
        $expansionRoutes[route('dungeonroutes.expansion', ['expansion' => $expansion])] =
            sprintf('<img src="%s" alt="%s" style="width: 50px"/> %s',
                $expansion->getIconUrl(),
                __($expansion->name),
                __('view_common.layout.header.routes', ['expansion' => __($expansion->name)])
            );
    }
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
<header @if($headerId !== false) id="{{ $headerId }}" @endif
        class="ksg-header {{ $forceShrink ? 'ksg-header--shrink ksg-header--shrink-forced' : '' }}">
@if($showGameVersionSelection || $showDungeonContext)
<div
    class="game_version_header navbar-first d-none d-lg-block
     {{ User::isThemeDark($theme) ? 'navbar-dark' : 'navbar-light' }}">
    <div class="container discover bg-dark rounded ">
        @if($showGameVersionSelection)
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
        @endif
        @if($showDungeonContext)
            <div class="row g-0 dungeon_context_header">
                <div class="col">
                    @include('common.dungeon.list', [
                        'gameVersion' => $currentUserGameVersion,
                        'dungeons' => $gameVersionDungeons,
                        'colCount' => $gameVersionDungeons->count(),
                        'useAbbreviation' => true,
                        'selectable' => true,
                        'showMore' => $showMore,
                        // Only set when the next season is close enough to be advertised (#3761)
                        'nextSeason' => $dungeonContextNextSeason,
                        'nextSeasonLink' => $dungeonContextNextSeasonLink,
                        'selected' => Dungeon::getUserOrDefaultDungeon()->key,
                        // "What's easy this week" ease tiers (archon.gg), resolved in HeaderComposer.
                        'easeTiers' => $dungeonContextEaseTiers ?? collect(),
                        'currentAffixGroup' => $dungeonContextCurrentAffixGroup ?? null,
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
@endif
<nav
    class="navbar navbar-second navbar-expand-lg
     {{ User::isThemeDark($theme) ? 'navbar-dark' : 'navbar-light' }}">
    <div class="container px-1 bg-header rounded">
        <a class="navbar-brand" href="/">
            <img src="{{ ksgAssetImage('logo/logo_and_text.png') }}" alt="{{ config('app.name') }}"
                 height="44px;" width="200px;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false"
                aria-label="{{ __('view_common.layout.header.toggle_navigation_title') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse text-center text-lg-start" id="mainNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item px-3">
                    <a class="btn btn-accent" href="#"
                       data-bs-toggle="modal" data-bs-target="#create_route_modal">
                        <i class="fas fa-plus"></i> {{__('view_common.layout.header.create_route')}}
                    </a>
                </li>
                @foreach($navs as $route => $opts)
                    @if($opts === 'divider')
                        <li class="nav-item nav-item-divider"></li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link pe-3 {{ $isActiveRoute($route) }}"
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
                        $compendiumContextDungeon = Dungeon::getUserOrDefaultDungeon();
                        $compendiumRoutes         = [
                            route('compendium.index')                                                      => sprintf('%s %s', '<i class="fas fa-book-open"></i>', __('view_common.layout.header.compendium_overview')),
                            route('npc.compendium.index.dungeon', ['dungeon' => $compendiumContextDungeon])   => sprintf('%s %s', '<i class="fas fa-dragon"></i>', __('view_common.layout.header.npc_compendium')),
                            route('spell.compendium.index.dungeon', ['dungeon' => $compendiumContextDungeon]) => sprintf('%s %s', '<i class="fas fa-magic"></i>', __('view_common.layout.header.spell_compendium')),
                            route('compendium.activity.index')                                               => sprintf('%s %s', '<i class="fas fa-stream"></i>', __('view_common.layout.header.compendium_activity')),
                            route('compendium.class.index')                                                  => sprintf('%s %s', '<i class="fas fa-hat-wizard"></i>', __('view_common.layout.header.class_compendium')),
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
                           data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-book-open"></i>
                            {{ $compendiumHeaderText }}
                        </a>
                        <div class="dropdown-menu text-center text-xl-start"
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
                @if($showExpansionNav)
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
                           data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-stream"></i>
                            {{ $headerText }}
                        </a>
                        <div class="dropdown-menu text-center text-xl-start" aria-labelledby="{{ $dropdownId }}">
                            @foreach($expansionRoutes as $itemKey => $item)
                                <a class="dropdown-item {{ $isActiveRoute($itemKey) }}"
                                   href="{{ $itemKey }}">{!! $item !!}</a>
                            @endforeach
                        </div>
                    </li>
                @endif
                @php($route = route('dungeon.explore.gameversion', ['gameVersion' => $currentUserGameVersion]))
                <li class="nav-item">
                    <a class="nav-link pe-3 {{ $isActiveRoute($route) }}"
                       href="{{ $route }}">
                        <i class="fas fa-compass"></i> {{ __('view_common.layout.header.explore') }}
                    </a>
                </li>
                <li class="nav-item nav-item-divider"></li>
                <li class="nav-item">
                    @if(Feature::active(SearchPageRework::class))
                        <a class="nav-link pe-3 {{ str_starts_with(Request::url(), route('dungeon.dungeonroute.search')) ? 'active' : '' }}"
                           href="{{ route('dungeon.dungeonroute.search') }}">
                            <i class="fas fa-search"></i>
                        </a>
                    @else
                        <a class="nav-link pe-3 {{ str_starts_with(Request::url(), route('dungeonroutes.search')) ? 'active' : '' }}"
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
</header>
