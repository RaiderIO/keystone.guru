@inject('discoverService', 'App\Service\DungeonRoute\DiscoverServiceInterface')
@inject('seasonService', 'App\Service\Season\SeasonService')
@extends('layouts.sitepage', ['title' => __('Routes'), 'custom' => true])

@section('header-title')
    {{ __('Routes') }}
@endsection
<?php
/**
 * @var $discoverService \App\Service\DungeonRoute\DiscoverService
 * @var $seasonService \App\Service\Season\SeasonService
 * @var $dungeons \App\Models\Dungeon[]|\Illuminate\Support\Collection
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

        <div class="discover_panel">

            <h2 class="text-center">
                {{ __('Discover routes') }}
            </h2>
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
            'dungeonroutes' => $dungeonroutes['thisweek'],
            'showMore' => true,
        ])
        @include('dungeonroute.discover.panel', [
            'title' => __('Popular routes by next week\'s affix'),
            'link' => route('dungeonroutes.nextweek'),
            'dungeonroutes' => $dungeonroutes['nextweek'],
            'showMore' => true,
        ])
        @include('dungeonroute.discover.panel', [
            'title' => __('Newly uploaded routes'),
            'link' => route('dungeonroutes.nextweek'),
            'dungeonroutes' => $dungeonroutes['new'],
            'showMore' => true,
        ])
    </div>
@endsection