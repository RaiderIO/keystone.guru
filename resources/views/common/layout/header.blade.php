<?php

use App\Features\Heatmap;
use App\Features\NpcCompendium;
use App\Features\SearchPageRework;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * @var GameVersion                  $currentUserGameVersion
 * @var Collection<int, GameVersion> $allGameVersions
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

// Resolved once and shared by the desktop dungeon-context strip and its mobile dropdown counterpart:
// explore, heatmap, the compendiums, search and discover all override these links so that picking a
// dungeon keeps you on the page type you were already on. Both selectors must honour that override.
$dungeonContextSelectedDungeon = null;
$resolvedDungeonContextLinks   = null;
if ($showDungeonContext) {
    $dungeonContextSelectedDungeon = Dungeon::getUserOrDefaultDungeon();
    $resolvedDungeonContextLinks   = $dungeonContextLinks ?? $gameVersionDungeons->mapWithKeys(fn(Dungeon $dungeon) => [
        $dungeon->key => route('dungeon.changecontext', [
            'dungeon' => $dungeon,
        ]),
    ]);
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

$searchRoute = Feature::active(SearchPageRework::class)
    ? route('dungeon.dungeonroute.search')
    : route('dungeonroutes.search');

$routeEntries = [
    [
        'route'       => route('dungeonroutes.gameVersion', ['gameVersion' => $currentUserGameVersion]),
        'fa'          => 'fa fa-route',
        'text'        => __('view_common.layout.header.browse_routes'),
        'description' => __('view_common.layout.header.browse_routes_description'),
    ],
    [
        'route'       => $searchRoute,
        'fa'          => 'fas fa-search',
        'text'        => __('view_common.layout.header.find_routes'),
        'description' => __('view_common.layout.header.find_routes_description'),
    ],
    [
        'modal'       => '#create_route_modal',
        'fa'          => 'fas fa-plus',
        'text'        => __('view_common.layout.header.create_route'),
        'description' => __('view_common.layout.header.create_route_description'),
    ],
];

$dungeonEntries = [];
if (Feature::active(Heatmap::class) && $currentUserGameVersion->key === GameVersion::GAME_VERSION_RETAIL) {
    $dungeonEntries[] = [
        'route'       => route('dungeon.heatmap.gameversion', ['gameVersion' => $currentUserGameVersion]),
        'fa'          => 'fas fa-fire text-danger',
        'text'        => __('view_common.layout.header.heatmaps'),
        'description' => __('view_common.layout.header.heatmaps_description'),
    ];
}
$dungeonEntries[] = [
    'route'       => route('dungeon.explore.gameversion', ['gameVersion' => $currentUserGameVersion]),
    'fa'          => 'fas fa-compass',
    'text'        => __('view_common.layout.header.explore'),
    'description' => __('view_common.layout.header.explore_description'),
];

$compendiumEntries = [];
if (Feature::active(NpcCompendium::class)) {
    $compendiumContextDungeon = Dungeon::getUserOrDefaultDungeon();
    $compendiumEntries        = [
        [
            'route'       => route('compendium.index'),
            'fa'          => 'fas fa-book-open',
            'text'        => __('view_common.layout.header.compendium_overview'),
            'description' => __('view_common.layout.header.compendium_overview_description'),
            'strict'      => true,
        ],
        [
            'route'       => route('npc.compendium.index.dungeon', ['dungeon' => $compendiumContextDungeon]),
            'fa'          => 'fas fa-dragon',
            'text'        => __('view_common.layout.header.npc_compendium'),
            'description' => __('view_common.layout.header.npc_compendium_description'),
            'strict'      => true,
        ],
        [
            'route'       => route('spell.compendium.index.dungeon', ['dungeon' => $compendiumContextDungeon]),
            'fa'          => 'fas fa-magic',
            'text'        => __('view_common.layout.header.spell_compendium'),
            'description' => __('view_common.layout.header.spell_compendium_description'),
            'strict'      => true,
        ],
        [
            'route'       => route('compendium.class.index'),
            'fa'          => 'fas fa-hat-wizard',
            'text'        => __('view_common.layout.header.class_compendium'),
            'description' => __('view_common.layout.header.class_compendium_description'),
            'strict'      => true,
        ],
        [
            'route'       => route('compendium.tuning.index'),
            'fa'          => 'fas fa-balance-scale',
            'text'        => __('view_common.layout.header.compendium_tuning'),
            'description' => __('view_common.layout.header.compendium_tuning_description'),
            'strict'      => true,
        ],
        [
            'route'       => route('compendium.activity.index'),
            'fa'          => 'fas fa-stream',
            'text'        => __('view_common.layout.header.compendium_activity'),
            'description' => __('view_common.layout.header.compendium_activity_description'),
            'strict'      => true,
        ],
    ];
}
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
                        'selected' => $dungeonContextSelectedDungeon->key,
                        // "What's easy this week" ease tiers (archon.gg), resolved in HeaderComposer.
                        'easeTiers' => $dungeonContextEaseTiers ?? collect(),
                        'currentAffixGroup' => $dungeonContextCurrentAffixGroup ?? null,
                        'links' => $resolvedDungeonContextLinks,
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
                 height="44" width="200">
        </a>
        {{-- Deliberately outside the collapse: the dungeon context is the topmost, most prominent bar
             on desktop, so on mobile it stays one tap away rather than buried in the hamburger (#4097) --}}
        @if($showDungeonContext)
            <ul class="navbar-nav flex-row d-lg-none">
                @include('common.layout.nav.dungeoncontext', [
                    'gameVersion' => $currentUserGameVersion,
                    'dungeons' => $gameVersionDungeons,
                    'showMore' => $showMore,
                    'selectedDungeon' => $dungeonContextSelectedDungeon,
                    'links' => $resolvedDungeonContextLinks,
                    'nextSeason' => $dungeonContextNextSeason,
                    'nextSeasonLink' => $dungeonContextNextSeasonLink,
                    'easeTiers' => $dungeonContextEaseTiers ?? collect(),
                    'currentAffixGroup' => $dungeonContextCurrentAffixGroup ?? null,
                ])
            </ul>
        @endif
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false"
                aria-label="{{ __('view_common.layout.header.toggle_navigation_title') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse text-center text-lg-start" id="mainNavbar">
            <ul class="navbar-nav me-auto">
                @include('common.layout.nav.category', [
                    'id' => 'navCategoryRoutes',
                    'fa' => 'fa fa-route',
                    'text' => __('view_common.layout.header.category_routes'),
                    'entries' => $routeEntries,
                    'columns' => 1,
                    'isActiveRoute' => $isActiveRoute,
                ])
                @include('common.layout.nav.category', [
                    'id' => 'navCategoryDungeons',
                    'fa' => 'fas fa-dungeon',
                    'text' => __('view_common.layout.header.category_dungeons'),
                    'entries' => $dungeonEntries,
                    'columns' => 1,
                    'isActiveRoute' => $isActiveRoute,
                ])
                @if($compendiumEntries !== [])
                    @include('common.layout.nav.category', [
                        'id' => 'navCategoryCompendium',
                        'fa' => 'fas fa-book-open',
                        'text' => __('view_common.layout.header.compendium'),
                        'entries' => $compendiumEntries,
                        'columns' => 2,
                        'isActiveRoute' => $isActiveRoute,
                    ])
                @endif
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item px-2">
                    <a class="btn btn-accent" href="#"
                       data-bs-toggle="modal" data-bs-target="#create_route_modal">
                        <i class="fas fa-plus"></i> {{__('view_common.layout.header.create_route')}}
                    </a>
                </li>
                @include('common.layout.nav.gameversions')
                @include('common.layout.nav.user')
            </ul>
        </div>
    </div>
</nav>
</header>
