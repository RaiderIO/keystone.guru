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

        <div class="discover_panel">
            <div class="row mt-4">
                <div class="col-xl">
                    <h2 class="text-center">
                        {{ __('Popular routes') }}
                    </h2>
                    @include('common.dungeonroute.cardlist', ['cols' => 2, 'dungeonroutes' => $discoverService->popular()])
                </div>
            </div>
        </div>

        <div class="discover_panel">
            <div class="row mt-4">
                <div class="col-xl">
                    <h2 class="text-center">
                        {{ __('Popular routes by current affix') }}
                    </h2>
                    @include('common.dungeonroute.cardlist', [
                        'cols' => 2,
                        'dungeonroutes' => $discoverService->popularByAffixGroup($seasonService->getCurrentSeason()->getCurrentAffixGroup())
                    ])
                </div>
            </div>
        </div>

        <div class="discover_panel">
            <div class="row mt-4">
                <div class="col-xl">
                    <h2 class="text-center">
                        {{ __('Popular routes by next week\'s affix') }}
                    </h2>
                    @include('common.dungeonroute.cardlist', [
                        'cols' => 2,
                        'dungeonroutes' => $discoverService->popularByAffixGroup($seasonService->getCurrentSeason()->getAffixGroupAtTime(now()->addDays(7)))
                    ])
                </div>
            </div>
        </div>

        <div class="discover_panel">
            <div class="row mt-4">
                <div class="col-xl">
                    <h2 class="text-center">
                        {{ __('Newly uploaded routes') }}
                    </h2>
                    @include('common.dungeonroute.cardlist', ['cols' => 2, 'dungeonroutes' => $discoverService->new()])
                </div>
            </div>
        </div>

        <!--    --><?php
        //    DB::enableQueryLog();
        //
        //    dump($discoverService->popularByAffixGroup($seasonService->getCurrentSeason()->getCurrentAffixGroup()));
        //
        //    dump(DB::getQueryLog())
        //    ?>

    </div>
@endsection