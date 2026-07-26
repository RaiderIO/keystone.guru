<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, DungeonRoute> $ownDungeonRoutes
 * @var array<int, int>               $selectedDungeonRouteIds
 * @var Collection<int, Team>         $teams
 * @var Collection<int, DungeonRouteCollectionCategory> $categories
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_collection.new.title')])

@section('header-title', __('view_collection.new.header'))

@section('content')
    <div class="container">
        @include('common.collection.details', [
            'dungeonRouteCollection' => null,
            'ownDungeonRoutes' => $ownDungeonRoutes,
            'selectedDungeonRouteIds' => $selectedDungeonRouteIds,
            'teams' => $teams,
            'categories' => $categories,
        ])
    </div>
@endsection
