@extends('layouts.sitepage', ['rootClass' => 'discover col-xl-10 offset-xl-1', 'breadcrumbs' => 'dungeonroutes', 'title' => __('Routes')])

<?php
/**
 * @var $dungeons \App\Models\Dungeon[]|\Illuminate\Support\Collection
 * @var $currentAffixGroup \App\Models\AffixGroup
 * @var $nextAffixGroup \App\Models\AffixGroup
 */
?>
@include('common.general.inline', ['path' => 'dungeonroute/discover/discover',
        'options' =>  [
        ]
])

@section('content')
    <div class="discover_panel">
        @include('common.dungeon.griddiscover', [
            'dungeons' => $dungeons,
            'links' => $dungeons->map(function(\App\Models\Dungeon $dungeon){
                return ['dungeon' => $dungeon->key, 'link' => route('dungeonroutes.discoverdungeon', ['dungeon' => $dungeon->slug])];
            })
        ])
    </div>

    @include('dungeonroute.discover.panel', [
        'title' => __('views/dungeonroute.discover.dungeon.overview.popular'),
        'link' => route('dungeonroutes.popular'),
        'currentAffixGroup' => $currentAffixGroup,
        'dungeonroutes' => $dungeonroutes['popular'],
        'showMore' => false,
        'showDungeonImage' => true,
    ])
    @include('dungeonroute.discover.panel', [
        'title' => __('views/dungeonroute.discover.discover.popular_by_current_affixes'),
        'link' => route('dungeonroutes.thisweek'),
        'currentAffixGroup' => $currentAffixGroup,
        'affixgroup' => $currentAffixGroup,
        'dungeonroutes' => $dungeonroutes['thisweek'],
        'showMore' => true,
        'showDungeonImage' => true,
    ])

    @if( !$adFree && !$isMobile)
        <div align="center" class="mt-4">
            @include('common.thirdparty.adunit', ['id' => 'site_middle_discover', 'type' => 'header', 'reportAdPosition' => 'top-right'])
        </div>
    @endif

    @include('dungeonroute.discover.panel', [
        'title' => __('views/dungeonroute.discover.dungeon.overview.popular_by_next_affixes'),
        'link' => route('dungeonroutes.nextweek'),
        // The next week's affix group is current for that week
        'currentAffixGroup' => $nextAffixGroup,
        'affixgroup' => $nextAffixGroup,
        'dungeonroutes' => $dungeonroutes['nextweek'],
        'showMore' => true,
        'showDungeonImage' => true,
    ])
    @include('dungeonroute.discover.panel', [
        'title' => __('views/dungeonroute.discover.discover.newly_published_routes'),
        'link' => route('dungeonroutes.new'),
        'currentAffixGroup' => $currentAffixGroup,
        'dungeonroutes' => $dungeonroutes['new'],
        'showMore' => true,
        'showDungeonImage' => true,
        'cache' => false,
    ])

    @component('common.general.modal', ['id' => 'userreport_dungeonroute_modal'])
        @include('common.modal.userreport.dungeonroute')
    @endcomponent
@endsection