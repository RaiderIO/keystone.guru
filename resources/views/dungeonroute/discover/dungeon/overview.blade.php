@extends('layouts.sitepage', ['custom' => true, 'title' => sprintf('%s routes', $dungeon->name)])

@section('header-title')
    {{ sprintf('%s routes', $dungeon->name) }}
@endsection
<?php
/**
 * @var $showAds boolean
 * @var $isMobile boolean
 * @var $dungeon \App\Models\Dungeon
 * @var $dungeonroutes array
 */
?>

@include('common.general.inline', ['path' => 'dungeonroute/discover/discover',
        'options' =>  [
        ]
])

@section('content')
    @include('dungeonroute.discover.wallpaper', ['dungeon' => $dungeon])

    <div class="container discover">
        <div class="mt-4">
            {{ Diglactic\Breadcrumbs\Breadcrumbs::render('dungeonroutes.discoverdungeon', $dungeon) }}
        </div>

        @if( $showAds && !$isMobile)
            <div align="center" class="mt-4">
                @include('common.thirdparty.adunit', ['id' => 'site_top_header', 'type' => 'header', 'reportAdPosition' => 'top-right'])
            </div>
        @endif

        @include('dungeonroute.discover.panel', [
            'title' => __('Popular'),
            'showMore' => true,
            'link' => route('dungeonroutes.discoverdungeon.popular', ['dungeon' => $dungeon]),
            'dungeonroutes' => $dungeonroutes['popular']
        ])
        @include('dungeonroute.discover.panel', [
            'title' => __('This week'),
            'showMore' => true,
            'link' => route('dungeonroutes.discoverdungeon.thisweek', ['dungeon' => $dungeon]),
            'dungeonroutes' => $dungeonroutes['thisweek'],
        ])

        @if( $showAds && !$isMobile)
            <div align="center" class="mt-4">
                @include('common.thirdparty.adunit', ['id' => 'site_middle_discover', 'type' => 'header', 'reportAdPosition' => 'top-right'])
            </div>
        @endif

        @include('dungeonroute.discover.panel', [
            'title' => __('Next week'),
            'showMore' => true,
            'link' => route('dungeonroutes.discoverdungeon.nextweek', ['dungeon' => $dungeon]),
            'dungeonroutes' => $dungeonroutes['nextweek']
        ])
        @include('dungeonroute.discover.panel', [
            'title' => __('New'),
            'showMore' => true,
            'link' => route('dungeonroutes.discoverdungeon.new', ['dungeon' => $dungeon]),
            'dungeonroutes' => $dungeonroutes['new']
        ])
    </div>
@endsection