@extends('layouts.sitepage', ['title' => __('Routes'), 'custom' => true])

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
    <div class="container discover">

        <div class="mt-4">
            {{ Diglactic\Breadcrumbs\Breadcrumbs::render('dungeonroutes') }}
        </div>

        @if( $showAds && !$isMobile)
            <div align="center" class="mt-4">
                @include('common.thirdparty.adunit', ['id' => 'site_top_header', 'type' => 'header', 'reportAdPosition' => 'top-right'])
            </div>
        @endif

        <div class="discover_panel">
            @include('common.dungeon.griddiscover', [
                'dungeons' => $dungeons,
                'links' => $dungeons->map(function(\App\Models\Dungeon $dungeon){
                    return ['dungeon' => $dungeon->key, 'link' => route('dungeonroutes.discoverdungeon', ['dungeon' => $dungeon->getSlug()])];
                })
            ])
        </div>

        @include('dungeonroute.discover.panel', [
            'title' => __('Popular routes'),
            'link' => route('dungeonroutes.popular'),
            'dungeonroutes' => $dungeonroutes['popular'],
            'showMore' => true,
        ])
        @include('dungeonroute.discover.panel', [
            'title' => __('Popular routes by current affixes'),
            'link' => route('dungeonroutes.thisweek'),
            'affixgroup' => $currentAffixGroup,
            'dungeonroutes' => $dungeonroutes['thisweek'],
            'showMore' => true,
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
        ])
        @include('dungeonroute.discover.panel', [
            'title' => __('Newly published routes'),
            'link' => route('dungeonroutes.nextweek'),
            'dungeonroutes' => $dungeonroutes['new'],
            'showMore' => true,
        ])
    </div>
@endsection