@inject('discoverService', 'App\Service\DungeonRoute\DiscoverServiceInterface')
@inject('seasonService', 'App\Service\Season\SeasonService')
@extends('layouts.sitepage', ['title' => __('Routes')])

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
    <div class="discover">
        <h2>
            {{ __('Discover by dungeon') }}
        </h2>
        @include('common.dungeon.grid', [
            'dungeons' => $dungeons,
            'links' => $dungeons->map(function(\App\Models\Dungeon $dungeon){
                return ['dungeon' => $dungeon->key, 'link' => route('dungeonroutes.discoverdungeon', ['dungeon' => $dungeon->slug])];
            })
        ])

        <div class="row mt-4">
            <div class="col-xl">
                <h2>
                    {{ __('Popular routes') }}
                </h2>
                @include('common.dungeonroute.cardlist', ['cols' => 2, 'dungeonroutes' => $discoverService->popular()])
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-xl">
                <h2>
                    {{ __('Popular routes by current affix') }}
                </h2>
                @include('common.dungeonroute.cardlist', [
                    'cols' => 2,
                    'dungeonroutes' => $discoverService->popularByAffixGroup($seasonService->getCurrentSeason()->getCurrentAffixGroup())
                ])
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-xl">
                <h2>
                    {{ __('Popular routes by next week\'s affix') }}
                </h2>
                @include('common.dungeonroute.cardlist', [
                    'cols' => 2,
                    'dungeonroutes' => $discoverService->popularByAffixGroup($seasonService->getCurrentSeason()->getAffixGroupAtTime(now()->addDays(7)))
                ])
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-xl">
                <h2>
                    {{ __('Newly uploaded routes') }}
                </h2>
                @include('common.dungeonroute.cardlist', ['cols' => 2, 'dungeonroutes' => $discoverService->new()])
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