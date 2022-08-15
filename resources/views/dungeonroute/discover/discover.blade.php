@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-10 offset-xl-1',
    'disableDefaultRootClasses' => true,
    'breadcrumbs' => $breadcrumbs,
    'breadcrumbsParams' => $breadcrumbsParams,
    'title' => __('views/dungeonroute.discover.discover.title')
])

<?php
/**
 * @var $expansion \App\Models\Expansion
 * @var $gridDungeons \App\Models\Dungeon[]|\Illuminate\Support\Collection
 * @var $currentAffixGroup \App\Models\AffixGroup\AffixGroup
 * @var $nextAffixGroup \App\Models\AffixGroup\AffixGroup
 */
?>
@include('common.general.inline', ['path' => 'dungeonroute/discover/discover',
        'options' =>  [
        ]
])

@section('content')
    @include('dungeonroute.discover.wallpaper', ['expansion' => $expansion])

    <div class="discover_panel">
        @include('common.dungeon.griddiscover', [
            'expansion' => $expansion,
            'dungeons' => $gridDungeons,
            'currentAffixGroup' => $currentAffixGroup,
            'nextAffixGroup' => $nextAffixGroup,
            'colCount' => 4,
            'links' => $gridDungeons->map(function(\App\Models\Dungeon $dungeon) use($expansion) {
                return ['dungeon' => $dungeon->key, 'link' => route('dungeonroutes.discoverdungeon', ['expansion' => $expansion, 'dungeon' => $dungeon->slug])];
            })
        ])
    </div>

    @include('dungeonroute.discover.panel', [
        'expansion' => $expansion,
        'title' => __('views/dungeonroute.discover.discover.popular'),
        'link' => route('dungeonroutes.popular', ['expansion' => $expansion]),
        'currentAffixGroup' => $currentAffixGroup,
        'dungeonroutes' => $dungeonroutes['popular'],
        'showMore' => $dungeonroutes['popular']->count() >= config('keystoneguru.discover.limits.overview'),
        'showDungeonImage' => true,
    ])
    @include('dungeonroute.discover.panel', [
        'expansion' => $expansion,
        'title' => __('views/dungeonroute.discover.discover.popular_by_current_affixes'),
        'link' => route('dungeonroutes.thisweek', ['expansion' => $expansion]),
        'currentAffixGroup' => $currentAffixGroup,
        'affixgroup' => $currentAffixGroup,
        'dungeonroutes' => $dungeonroutes['thisweek'],
        'showMore' => $dungeonroutes['thisweek']->count() >= config('keystoneguru.discover.limits.overview'),
        'showDungeonImage' => true,
    ])

    @if( !$adFree && !$isMobile)
        <div align="center" class="mt-4">
            @include('common.thirdparty.adunit', ['id' => 'site_middle_discover', 'type' => 'header', 'reportAdPosition' => 'top-right'])
        </div>
    @endif

    @include('dungeonroute.discover.panel', [
        'expansion' => $expansion,
        'title' => __('views/dungeonroute.discover.discover.popular_by_next_affixes'),
        'link' => route('dungeonroutes.nextweek', ['expansion' => $expansion]),
        // The next week's affix group is current for that week
        'currentAffixGroup' => $nextAffixGroup,
        'affixgroup' => $nextAffixGroup,
        'dungeonroutes' => $dungeonroutes['nextweek'],
        'showMore' => $dungeonroutes['nextweek']->count() >= config('keystoneguru.discover.limits.overview'),
        'showDungeonImage' => true,
    ])
    @include('dungeonroute.discover.panel', [
        'expansion' => $expansion,
        'title' => __('views/dungeonroute.discover.discover.newly_published_routes'),
        'link' => route('dungeonroutes.new', ['expansion' => $expansion]),
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
