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
        <div class="discover_panel">
            <h2 class="text-center">
                {{ __('Discover routes') }}
            </h2>
            @include('common.dungeon.griddiscover', [
                'dungeons' => $dungeons,
                'links' => $dungeons->map(function(\App\Models\Dungeon $dungeon){
                    return ['dungeon' => $dungeon->key, 'link' => route('dungeonroutes.discoverdungeon', ['dungeon' => $dungeon->slug])];
                })
            ])
        </div>

        @include('dungeonroute.discover.dungeon.panel', ['title' => __('Popular routes'), 'dungeonroutes' => $dungeonroutes['popular']])
        @include('dungeonroute.discover.dungeon.panel', ['title' => __('Popular routes by current affix'), 'dungeonroutes' => $dungeonroutes['thisweek']])
        @include('dungeonroute.discover.dungeon.panel', ['title' => __('Popular routes by next week\'s affix'), 'dungeonroutes' => $dungeonroutes['nextweek']])
        @include('dungeonroute.discover.dungeon.panel', ['title' => __('Newly uploaded routes'), 'dungeonroutes' => $dungeonroutes['new']])
    </div>
@endsection