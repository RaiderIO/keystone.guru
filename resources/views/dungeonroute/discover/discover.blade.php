@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-8 offset-xl-2',
    'disableDefaultRootClasses' => true,
    'breadcrumbs' => $breadcrumbs,
    'breadcrumbsParams' => $breadcrumbsParams,
    'title' => __('view_dungeonroute.discover.discover.title'),
])
<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * @var GameVersion         $currentUserGameVersion
 * @var GameVersion         $gameVersion
 * @var Expansion           $expansion
 * @var Season|null         $season
 * @var Collection<Dungeon> $gridDungeons
 * @var AffixGroup          $currentAffixGroup
 * @var AffixGroup          $nextAffixGroup
 */
?>
@include('common.general.inline', ['path' => 'dungeonroute/discover/discover'])

@section('content')
    @include('dungeonroute.discover.wallpaper', ['gameVersion' => $gameVersion])

    <div class="discover_panel">
        @include('common.dungeon.griddiscover', [
            'gameVersion' => $gameVersion,
            'dungeons' => $gridDungeons,
            'currentAffixGroup' => $currentAffixGroup,
            'nextAffixGroup' => $nextAffixGroup,
            'colCount' => 4,
            'links' => $gridDungeons->map(function(Dungeon $dungeon) use($gameVersion) {
                return ['dungeon' => $dungeon->key, 'link' => route('dungeonroutes.discoverdungeon', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon->slug])];
            }),
        ])
    </div>

    @include('dungeonroute.discover.panel', [
        'gameVersion' => $gameVersion,
        'title' => __('view_dungeonroute.discover.discover.popular'),
        'link' => isset($season) ?
            route('dungeonroutes.season.popular', ['gameVersion' => $gameVersion, 'season' => $season->index]) :
            route('dungeonroutes.popular', ['gameVersion' => $gameVersion]),
        'currentAffixGroup' => $currentAffixGroup,
        'dungeonroutes' => $dungeonroutes['popular'],
        'showMore' => $dungeonroutes['popular']->count() >= config('keystoneguru.discover.limits.overview'),
        'showDungeonImage' => true,
    ])

    @if(!$adFree && !$isMobile)
        <div align="center" class="mt-4">
            @include('common.thirdparty.adunit', ['id' => 'site_middle_discover', 'type' => 'header_middle', 'reportAdPosition' => 'top-right'])
        </div>
    @endif

    @if($currentUserGameVersion->has_seasons)
        @if($currentAffixGroup !== null)
            @include('dungeonroute.discover.panel', [
                'gameVersion' => $gameVersion,
                'title' => __('view_dungeonroute.discover.discover.popular_by_current_affixes'),
                'link' => isset($season) ?
                    route('dungeonroutes.season.thisweek', ['gameVersion' => $gameVersion, 'season' => $season->index]) :
                    route('dungeonroutes.thisweek', ['gameVersion' => $gameVersion]) ,
                'currentAffixGroup' => $currentAffixGroup,
                'affixgroup' => $currentAffixGroup,
                'dungeonroutes' => $dungeonroutes['thisweek'],
                'showMore' => $dungeonroutes['thisweek']->count() >= config('keystoneguru.discover.limits.overview'),
                'showDungeonImage' => true,
            ])
        @endif

        @if( !$adFree && !$isMobile)
            <div align="center" class="mt-4">
                @include('common.thirdparty.adunit', ['id' => 'site_middle_discover', 'type' => 'header_middle', 'reportAdPosition' => 'top-right'])
            </div>
        @endif

        @if($nextAffixGroup !== null)
            @include('dungeonroute.discover.panel', [
                'gameVersion' => $gameVersion,
                'title' => __('view_dungeonroute.discover.discover.popular_by_next_affixes'),
                'link' => isset($season) ?
                    route('dungeonroutes.season.nextweek', ['gameVersion' => $gameVersion, 'season' => $season->index]) :
                    route('dungeonroutes.nextweek', ['gameVersion' => $gameVersion]),
                'currentAffixGroup' => $nextAffixGroup,
                'affixgroup' => $nextAffixGroup,
                'dungeonroutes' => $dungeonroutes['nextweek'],
                'showMore' => $dungeonroutes['nextweek']->count() >= config('keystoneguru.discover.limits.overview'),
                'showDungeonImage' => true,
            ])
        @endif
    @endif

    @if( !$adFree && !$isMobile)
        <div align="center" class="mt-4">
            @include('common.thirdparty.adunit', ['id' => 'site_middle_discover', 'type' => 'header_middle', 'reportAdPosition' => 'top-right'])
        </div>
    @endif

    @include('dungeonroute.discover.panel', [
        'gameVersion' => $gameVersion,
        'title' => __('view_dungeonroute.discover.discover.newly_published_routes'),
        'link' => isset($season) ?
            route('dungeonroutes.season.new', ['gameVersion' => $gameVersion, 'season' => $season->index]) :
            route('dungeonroutes.new', ['gameVersion' => $gameVersion]),
        'currentAffixGroup' => $currentAffixGroup,
        'dungeonroutes' => $dungeonroutes['new'],
        'showMore' => $dungeonroutes['new']->count() >= config('keystoneguru.discover.limits.overview'),
        'showDungeonImage' => true,
        'cache' => false,
    ])

    @component('common.general.modal', ['id' => 'userreport_dungeonroute_modal'])
        @include('common.modal.userreport.dungeonroute')
    @endcomponent
@endsection
