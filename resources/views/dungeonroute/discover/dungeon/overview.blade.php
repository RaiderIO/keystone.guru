<?php

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use App\Service\DungeonRoute\DungeonRouteEnemyForcesPageResolver;
use App\Service\DungeonRoute\DungeonRouteKillZoneServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @var boolean                     $showAds
 * @var boolean                     $isMobile
 * @var Dungeon                     $dungeon
 * @var LengthAwarePaginator<int, DungeonRoute> $paginator The paginated popular routes.
 * @var Collection<int, WeeklyRoute> $weeklyRoutes
 * @var GameVersion                 $gameVersion
 * @var Collection<int, Dungeon>    $gameVersionDungeons
 */
?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-8 offset-xl-2',
    'disableDefaultRootClasses' => true,
    'breadcrumbsParams' => [$gameVersion, $dungeon],
    'title' => sprintf('%s routes', __($dungeon->name)),
    'dungeonContextLinks' => $gameVersionDungeons->mapWithKeys(fn (Dungeon $dungeon) => [
        $dungeon->key => route('dungeonroutes.discoverdungeon', [
            'gameVersion' => $gameVersion,
            'dungeon' => $dungeon,
        ])
    ]),
])

@include('common.general.inline', ['path' => 'dungeonroute/discover/discover',
        'options' =>  [
        ],
])

@section('scripts')
    @parent

    @include('common.handlebars.affixgroups')
@endsection

@section('content')
    @include('dungeonroute.discover.wallpaper', ['dungeon' => $dungeon])

    <?php
    $page    = $paginator->currentPage();
    $perPage = $paginator->perPage();
    // The current page of popular routes; the hero band (page 1 only) is carved out of these.
    $popularItems = collect($paginator->items());
    // The routes already promoted into the hero band are excluded from the leaderboard below.
    $heroRouteIds = collect();
    $startRank    = ($page - 1) * $perPage + 1;
    // Batches the per-pull enemy forces query across every card on this page - hero band and
    // leaderboard rows alike - into a single grouped query instead of one query per card. The
    // weekly-route hero band only ever renders on page 1, so it's only folded in there.
    $pullForcesResolver = new DungeonRouteEnemyForcesPageResolver(
        app(DungeonRouteKillZoneServiceInterface::class),
        ($page === 1 ? $weeklyRoutes->pluck('dungeonRoute') : collect())->merge($popularItems)->unique('id')->values(),
    );
    ?>
    <?php // Above the hero band, not between it and the leaderboard: startRank continues from the band into the list ?>
    @feature(\App\Features\CreatorProfiles::class)
        @include('creator.featured', ['dungeon' => $dungeon])
    @endfeature

    @if($page === 1)
        @if($weeklyRoutes->isNotEmpty())
            <div class="row mt-4 align-items-center discover_section_header">
                <div class="col">
                    <h5 class="mb-0 text-center">
                        <a href="{{ config('keystoneguru.raider_io.weekly_route.url') }}" target="_blank">
                            {{ __('view_dungeonroute.discover.dungeon.overview.weekly_routes') }}
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </h5>
                </div>
            </div>
            <div class="row g-3 mt-0 discover_hero_band">
                @foreach($weeklyRoutes as $weeklyRoute)
                    @php($heroRouteIds->push($weeklyRoute->dungeonRoute->id))
                    <div class="col">
                        @include('common.dungeonroute.cardhero', [
                            'dungeonroute' => $weeklyRoute->dungeonRoute,
                            'archetype' => $weeklyRoute->type,
                            'cache' => true,
                            'pullForcesResolver' => $pullForcesResolver,
                        ])
                    </div>
                @endforeach
            </div>
        @else
            <?php // With no Raider.IO weekly routes, the top community routes fill the hero band instead ?>
            <?php $fallbackHeroes = $popularItems->take(3)->values(); ?>
            @if($fallbackHeroes->isNotEmpty())
                <div class="row g-3 mt-4 discover_hero_band">
                    @foreach($fallbackHeroes as $index => $fallbackHero)
                        @php($heroRouteIds->push($fallbackHero->id))
                        <div class="col">
                            @include('common.dungeonroute.cardhero', [
                                'dungeonroute' => $fallbackHero,
                                'archetype' => null,
                                'heroRank' => $index + 1,
                                'cache' => true,
                                'pullForcesResolver' => $pullForcesResolver,
                            ])
                        </div>
                    @endforeach
                </div>
                <?php $startRank = $heroRouteIds->count() + 1; ?>
            @endif
        @endif
    @endif

    <?php
    /** @var Collection<int, DungeonRoute> $leaderboardRoutes */
    $leaderboardRoutes = $popularItems
        ->reject(fn(DungeonRoute $route) => $heroRouteIds->contains($route->id))
        ->values();
    ?>
    <?php // When every route already fills the hero band above, an empty "no routes" leaderboard here would be misleading ?>
    @if($leaderboardRoutes->isNotEmpty() || $heroRouteIds->isEmpty())
        <div class="row mt-5 align-items-center discover_section_header">
            <div class="col">
                <h5 class="mb-0 text-center">
                    {{ __('view_dungeonroute.discover.dungeon.overview.community_routes') }}
                </h5>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col">
                @include('common.dungeonroute.leaderboard', [
                    'dungeonroutes' => $leaderboardRoutes,
                    'startRank' => $startRank,
                    'cache' => true,
                    'pullForcesResolver' => $pullForcesResolver,
                ])
            </div>
        </div>
    @endif

    @if($paginator->hasPages())
        <div class="row mt-4">
            <div class="col d-flex justify-content-center discover_pagination">
                <?php // A compact window: the full page list otherwise overflows phone viewports ?>
                {{ $paginator->onEachSide(1)->links() }}
            </div>
        </div>
    @endif

    @if( !$adFree && !$isMobile)
        <div align="center" class="mt-4">
            @include('common.thirdparty.adunit', ['id' => 'site_middle_discover', 'type' => 'header', 'reportAdPosition' => 'top-right'])
        </div>
    @endif

    @component('common.general.modal', ['id' => 'userreport_dungeonroute_modal'])
        @include('common.modal.userreport.dungeonroute')
    @endcomponent
@endsection
