<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * @var DungeonRouteCollection        $dungeonRouteCollection
 * @var Collection<int, DungeonRoute> $ownDungeonRoutes
 * @var array<int, int>               $selectedDungeonRouteIds
 * @var Collection<int, Team>         $teams
 * @var Collection<int, DungeonRouteCollectionCategory> $categories
 */

$title = sprintf(__('view_collection.edit.title'), $dungeonRouteCollection->name);
?>
@extends('layouts.sitepage', [
    'showAds' => false,
    'title' => $title,
    'breadcrumbsParams' => [$dungeonRouteCollection],
])

@section('header-title', $title)
@section('header-addition')
    <a href="{{ route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]) }}"
       class="btn btn-info float-end" role="button">
        <i class="fas fa-external-link-alt"></i> {{ __('view_collection.edit.view_collection') }}
    </a>
@endsection

@section('content')
    <div class="container">
        @include('common.collection.details', [
            'dungeonRouteCollection' => $dungeonRouteCollection,
            'ownDungeonRoutes' => $ownDungeonRoutes,
            'selectedDungeonRouteIds' => $selectedDungeonRouteIds,
            'teams' => $teams,
            'categories' => $categories,
        ])
    </div>
@endsection
