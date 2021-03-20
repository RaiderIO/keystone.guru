@extends('layouts.sitepage', ['rootClass' => 'discover col-xl-10 offset-xl-1', 'breadcrumbs' => 'dungeonroutes', 'title' => __('Routes')])

@section('header-title')
    {{ __('Routes') }}
@endsection
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
        <div class="col-xl-8 offset-xl-2">
            @include('common.dungeon.griddiscover', [
                'dungeons' => $dungeons,
                'links' => $dungeons->map(function(\App\Models\Dungeon $dungeon){
                    return ['dungeon' => $dungeon->key, 'link' => route('dungeonroutes.discoverdungeon', ['dungeon' => $dungeon->getSlug()])];
                })
            ])
        </div>
    </div>

    {{--        @include('dungeonroute.discover.panel', [--}}
    {{--            'title' => __('Popular routes'),--}}
    {{--            'link' => route('dungeonroutes.popular'),--}}
    {{--            'dungeonroutes' => $dungeonroutes['popular'],--}}
    {{--            'showMore' => true,--}}
    {{--            'showDungeonImage' => true,--}}
    {{--        ])--}}
    @include('dungeonroute.discover.panel', [
        'title' => __('Popular routes by current affixes'),
        'link' => route('dungeonroutes.thisweek'),
        'affixgroup' => $currentAffixGroup,
        'dungeonroutes' => $dungeonroutes['thisweek'],
        'showMore' => true,
        'showDungeonImage' => true,
    ])

    @if( $showAds && !$isMobile)
        <div align="center" class="mt-4">
            @include('common.thirdparty.adunit', ['id' => 'site_middle_discover', 'type' => 'header', 'reportAdPosition' => 'top-right'])
        </div>
    @endif

    @include('dungeonroute.discover.panel', [
        'title' => __('Popular routes by next affixes'),
        'link' => route('dungeonroutes.nextweek'),
        'affixgroup' => $nextAffixGroup,
        'dungeonroutes' => $dungeonroutes['nextweek'],
        'showMore' => true,
        'showDungeonImage' => true,
    ])
    @include('dungeonroute.discover.panel', [
        'title' => __('Newly published routes'),
        'link' => route('dungeonroutes.new'),
        'dungeonroutes' => $dungeonroutes['new'],
        'showMore' => true,
        'showDungeonImage' => true,
    ])
@endsection