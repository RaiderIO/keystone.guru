<?php

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use Illuminate\Support\Collection;

/**
 * @var Collection<Dungeon>                         $weeklyRouteDungeons
 * @var Collection<string, Collection<WeeklyRoute>> $weeklyRoutes
 * @var Collection<DungeonRoute>                    $popularDungeonRoutesByDungeon
 */

?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-8 offset-xl-2',
])

@include('common.general.inline', ['path' => 'home/layout', 'options' => [

]])


@section('content')

    @if(GameVersion::getUserOrDefaultGameVersion()->key === GameVersion::GAME_VERSION_RETAIL)
        @include('home.sections.routes.weeklyroute', ['dungeons' => $weeklyRouteDungeons, 'weeklyRoutes' => $weeklyRoutes])
    @endif

    @include('home.sections.featured')

    @include('home.sections.about')

    @include('home.sections.features')

    @include('home.sections.routes.popular', ['dungeonRoutes' => $popularDungeonRoutesByDungeon])

    {{--    @include('home.sections.routes.funny')--}}

    {{--    @include('home.sections.routes.new')--}}
@endsection
