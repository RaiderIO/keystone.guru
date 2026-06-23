<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use Illuminate\Support\Collection;

/**
 * @var AffixGroup               $currentAffixGroup
 * @var boolean                  $showAds
 * @var boolean                  $isMobile
 * @var Dungeon                  $dungeon
 * @var array<int, DungeonRoute> $dungeonroutes
 * @var GameVersion              $gameVersion
 * @var Collection<int, Dungeon> $gameVersionDungeons
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

    @if($dungeonroutes['weekly_route']->isNotEmpty())
        @include('dungeonroute.discover.panel', [
            'gameVersion' => $gameVersion,
            'link' => config('keystoneguru.raider_io.weekly_route.url'),
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
@endsection
