@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-8 offset-xl-2',
    'disableDefaultRootClasses' => true,
    'breadcrumbsParams' => [$dungeon],
    'title' => sprintf('%s routes', __($dungeon->name)),
])
<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\Expansion;

/**
 * @var AffixGroup $currentAffixGroup
 * @var boolean    $showAds
 * @var boolean    $isMobile
 * @var Dungeon    $dungeon
 * @var array      $dungeonroutes
 * @var Expansion  $expansion
 */
?>

@include('common.general.inline', ['path' => 'dungeonroute/discover/discover',
        'options' =>  [
        ],
])

@section('content')
    @include('dungeonroute.discover.wallpaper', ['expansion' => null, 'dungeon' => $dungeon])

    @include('dungeonroute.discover.panel', [
        'expansion' => $expansion,
        'title' => __('view_dungeonroute.discover.dungeon.overview.popular'),
        'link' => route('dungeonroutes.discoverdungeon.popular', ['expansion' => $expansion, 'dungeon' => $dungeon]),
        'currentAffixGroup' => $currentAffixGroup,
        'dungeonroutes' => $dungeonroutes['popular'],
        'showMore' => $dungeonroutes['popular']->count() >= config('keystoneguru.discover.limits.overview'),
        'showDungeonImage' => $expansion->showDiscoverRoutesCardDungeonImage(),
    ])

    @if( !$adFree && !$isMobile)
        <div align="center" class="mt-4">
            @include('common.thirdparty.adunit', ['id' => 'site_middle_discover', 'type' => 'header', 'reportAdPosition' => 'top-right'])
        </div>
    @endif

    @if($dungeon->gameVersion->has_seasons)
        @include('dungeonroute.discover.panel', [
            'expansion' => $expansion,
            'title' => __('view_dungeonroute.discover.dungeon.overview.popular_by_current_affixes'),
            'link' => route('dungeonroutes.discoverdungeon.thisweek', ['expansion' => $expansion, 'dungeon' => $dungeon]),
            'currentAffixGroup' => $currentAffixGroup,
            'affixgroup' => $currentAffixGroup,
            'dungeonroutes' => $dungeonroutes['thisweek'],
            'showMore' => $dungeonroutes['thisweek']->count() >= config('keystoneguru.discover.limits.overview'),
            'showDungeonImage' => $expansion->showDiscoverRoutesCardDungeonImage(),
        ])
    @endif

    @if( !$adFree && !$isMobile)
        <div align="center" class="mt-4">
            @include('common.thirdparty.adunit', ['id' => 'site_middle_discover', 'type' => 'header', 'reportAdPosition' => 'top-right'])
        </div>
    @endif

    @if($dungeon->gameVersion->has_seasons)
        @include('dungeonroute.discover.panel', [
            'expansion' => $expansion,
            'title' => __('view_dungeonroute.discover.dungeon.overview.popular_by_next_affixes'),
            'link' => route('dungeonroutes.discoverdungeon.nextweek', ['expansion' => $expansion, 'dungeon' => $dungeon]),
            'currentAffixGroup' => $currentAffixGroup,
            'affixgroup' => $nextAffixGroup,
            'dungeonroutes' => $dungeonroutes['nextweek'],
            'showMore' => $dungeonroutes['nextweek']->count() >= config('keystoneguru.discover.limits.overview'),
            'showDungeonImage' => $expansion->showDiscoverRoutesCardDungeonImage(),
        ])
    @endif

    @if( !$adFree && !$isMobile)
        <div align="center" class="mt-4">
            @include('common.thirdparty.adunit', ['id' => 'site_middle_discover', 'type' => 'header', 'reportAdPosition' => 'top-right'])
        </div>
    @endif

    @include('dungeonroute.discover.panel', [
        'expansion' => $expansion,
        'title' => __('view_dungeonroute.discover.dungeon.overview.newly_published_routes'),
        'link' => route('dungeonroutes.discoverdungeon.new', ['expansion' => $expansion, 'dungeon' => $dungeon]),
        'currentAffixGroup' => $currentAffixGroup,
        'dungeonroutes' => $dungeonroutes['new'],
        'showMore' => $dungeonroutes['new']->count() >= config('keystoneguru.discover.limits.overview'),
        'showDungeonImage' => $expansion->showDiscoverRoutesCardDungeonImage(),
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
