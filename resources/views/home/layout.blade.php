<?php

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, Dungeon>                         $weeklyRouteDungeons
 * @var Collection<string, Collection<int, WeeklyRoute>> $weeklyRoutes
 * @var Collection<int, \App\Models\DungeonRoute\DungeonRoute>                    $popularDungeonRoutesByDungeon
 * @var bool                                        $adFree
 * @var bool                                        $isMobile
 */

?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-8 offset-xl-2',
])

@include('common.general.inline', ['path' => 'home/layout', 'options' => [

]])


@section('content')
    @include('home.sections.featured')

    @if(GameVersion::getUserOrDefaultGameVersion()->key === GameVersion::GAME_VERSION_RETAIL && $weeklyRoutes->isNotEmpty())
        @include('home.sections.routes.weeklyroute', ['dungeons' => $weeklyRouteDungeons, 'weeklyRoutes' => $weeklyRoutes])
    @endif


    @if(!$adFree && !$isMobile)
        <div align="center" class="mt-4">
            @include('common.thirdparty.adunit', ['id' => 'site_middle_discover', 'type' => 'header_middle', 'reportAdPosition' => 'top-right'])
        </div>
    @endif

    @include('home.sections.routes.popular', ['dungeonRoutes' => $popularDungeonRoutesByDungeon])

    @include('home.sections.about')
@endsection
