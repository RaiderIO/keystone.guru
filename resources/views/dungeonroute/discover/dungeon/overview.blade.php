<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;

/**
 * @var AffixGroup  $currentAffixGroup
 * @var boolean     $showAds
 * @var boolean     $isMobile
 * @var Dungeon     $dungeon
 * @var array       $dungeonroutes
 * @var GameVersion $gameVersion
 */

$dungeonHasMappingVersionWithSeasons = $dungeon->hasMappingVersionWithSeasons();
?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-8 offset-xl-2',
    'disableDefaultRootClasses' => true,
    'breadcrumbsParams' => [$gameVersion, $dungeon],
    'title' => sprintf('%s routes', __($dungeon->name)),
])

@include('common.general.inline', ['path' => 'dungeonroute/discover/discover',
        'options' =>  [
        ],
])

@section('content')
    @include('dungeonroute.discover.wallpaper', ['dungeon' => $dungeon])

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

    @if($dungeonHasMappingVersionWithSeasons)
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

    @if($dungeonHasMappingVersionWithSeasons)
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
