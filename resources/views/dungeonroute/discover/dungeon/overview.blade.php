<?php

use App\Features\DungeonRouteListRework;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;

/**
 * @var AffixGroup                  $currentAffixGroup
 * @var boolean                     $showAds
 * @var boolean                     $isMobile
 * @var Dungeon                     $dungeon
 * @var array<string, Collection<int, DungeonRoute>> $dungeonroutes
 * @var Collection<int, WeeklyRoute> $weeklyRoutes
 * @var GameVersion                 $gameVersion
 * @var Collection<int, Dungeon>    $gameVersionDungeons
 */

$showRoutesByAffixes = $gameVersion->has_seasons && $gameVersion->key !== GameVersion::GAME_VERSION_RETAIL;
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

    @if(Feature::active(DungeonRouteListRework::class))
        <?php
        // The routes already promoted into the hero band are excluded from the leaderboard below
        $heroRouteIds = collect();
        $startRank    = 1;
        ?>
        @if($weeklyRoutes->isNotEmpty())
            <div class="row mt-4 align-items-center discover_section_header">
                <div class="col">
                    <h5 class="mb-0">
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
                        ])
                    </div>
                @endforeach
            </div>
        @else
            <?php // With no Raider.IO weekly routes, the top community routes fill the hero band instead ?>
            <?php $fallbackHeroes = $dungeonroutes['popular']->take(3)->values(); ?>
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
                            ])
                        </div>
                    @endforeach
                </div>
                <?php $startRank = $heroRouteIds->count() + 1; ?>
            @endif
        @endif

        <?php
        /** @var Collection<int, DungeonRoute> $leaderboardRoutes */
        $leaderboardRoutes = $dungeonroutes['popular']
            ->reject(fn(DungeonRoute $route) => $heroRouteIds->contains($route->id))
            ->values();
        ?>
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
                ])
            </div>
        </div>

        @if($dungeonroutes['popular']->count() >= config('keystoneguru.discover.limits.overview'))
            <div class="row mt-4">
                <div class="col-xl text-center">
                    <a href="{{ route('dungeonroutes.discoverdungeon.popular', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon]) }}">
                        >> {{ __('view_dungeonroute.discover.panel.show_more') }}
                    </a>
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
    @else
    @if($dungeonroutes['weekly_route']->isNotEmpty())
        @include('dungeonroute.discover.panel', [
            'gameVersion' => $gameVersion,
            'link' => config('keystoneguru.raider_io.weekly_route.url'),
            'linkOptions' => [
                'target' => '_blank',
            ],
            'title' => __('view_dungeonroute.discover.dungeon.overview.weekly_route'),
            'dungeonroutes' => $dungeonroutes['weekly_route'],
            'showMore' => false,
            'showDungeonImage' => $gameVersion->showDiscoverRoutesCardDungeonImage(),
        ])
    @endif

    @include('dungeonroute.discover.panel', [
        'gameVersion' => $gameVersion,
        'title' => __('view_dungeonroute.discover.dungeon.overview.popular'),
        'link' => route('dungeonroutes.discoverdungeon.popular', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon]),
        'currentAffixGroup' => $currentAffixGroup,
        'dungeonroutes' => $dungeonroutes['popular'],
        'showMore' => $dungeonroutes['popular']->count() >= config('keystoneguru.discover.limits.overview'),
        'showDungeonImage' => $gameVersion->showDiscoverRoutesCardDungeonImage(),
    ])

    @if( !$adFree && !$isMobile)
        <div align="center" class="mt-4">
            @include('common.thirdparty.adunit', ['id' => 'site_middle_discover', 'type' => 'header', 'reportAdPosition' => 'top-right'])
        </div>
    @endif

    @if($showRoutesByAffixes)
        @include('dungeonroute.discover.panel', [
            'gameVersion' => $gameVersion,
            'title' => __('view_dungeonroute.discover.dungeon.overview.popular_by_current_affixes'),
            'link' => route('dungeonroutes.discoverdungeon.thisweek', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon]),
            'currentAffixGroup' => $currentAffixGroup,
            'affixgroup' => $currentAffixGroup,
            'dungeonroutes' => $dungeonroutes['thisweek'],
            'showMore' => $dungeonroutes['thisweek']->count() >= config('keystoneguru.discover.limits.overview'),
            'showDungeonImage' => $gameVersion->showDiscoverRoutesCardDungeonImage(),
        ])
    @endif

    @if( !$adFree && !$isMobile)
        <div align="center" class="mt-4">
            @include('common.thirdparty.adunit', ['id' => 'site_middle_discover', 'type' => 'header', 'reportAdPosition' => 'top-right'])
        </div>
    @endif

    @if($showRoutesByAffixes)
        @include('dungeonroute.discover.panel', [
            'gameVersion' => $gameVersion,
            'title' => __('view_dungeonroute.discover.dungeon.overview.popular_by_next_affixes'),
            'link' => route('dungeonroutes.discoverdungeon.nextweek', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon]),
            'currentAffixGroup' => $currentAffixGroup,
            'affixgroup' => $nextAffixGroup,
            'dungeonroutes' => $dungeonroutes['nextweek'],
            'showMore' => $dungeonroutes['nextweek']->count() >= config('keystoneguru.discover.limits.overview'),
            'showDungeonImage' => $gameVersion->showDiscoverRoutesCardDungeonImage(),
        ])
    @endif

    @if(!$adFree && !$isMobile)
        <div align="center" class="mt-4">
            @include('common.thirdparty.adunit', ['id' => 'site_middle_discover', 'type' => 'header', 'reportAdPosition' => 'top-right'])
        </div>
    @endif

    @include('dungeonroute.discover.panel', [
        'gameVersion' => $gameVersion,
        'title' => __('view_dungeonroute.discover.dungeon.overview.newly_published_routes'),
        'link' => route('dungeonroutes.discoverdungeon.new', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon]),
        'currentAffixGroup' => $currentAffixGroup,
        'dungeonroutes' => $dungeonroutes['new'],
        'showMore' => $dungeonroutes['new']->count() >= config('keystoneguru.discover.limits.overview'),
        'showDungeonImage' => $gameVersion->showDiscoverRoutesCardDungeonImage(),
    ])

    @if( !$adFree && !$isMobile)
        <div align="center" class="mt-4">
            @include('common.thirdparty.adunit', ['id' => 'site_middle_discover', 'type' => 'header', 'reportAdPosition' => 'top-right'])
        </div>
    @endif

    @component('common.general.modal', ['id' => 'userreport_dungeonroute_modal'])
        @include('common.modal.userreport.dungeonroute')
    @endcomponent
    @endif
@endsection
