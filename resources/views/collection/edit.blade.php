<?php

use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\Team;
use App\Service\DungeonRoute\Dtos\DungeonRouteCollectionGroup;
use Illuminate\Support\Collection;

/**
 * @var DungeonRouteCollection                          $dungeonRouteCollection
 * @var Collection<int, DungeonRouteCollectionGroup>    $editSections
 * @var bool                                            $hasOwnDungeonRoutes
 * @var bool                                            $mayAddDungeonRoutes
 * @var array<int, int>                                 $selectedDungeonRouteIds
 * @var GameVersion                                     $selectedGameVersion
 * @var Season|null                                     $selectedSeason
 * @var Collection<int, DungeonRoute>                  $ownDungeonRoutes
 * @var Collection<int, Team>                           $teams
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
        @include('common.collection.routes', [
            'dungeonRouteCollection' => $dungeonRouteCollection,
            'editSections' => $editSections,
            'hasOwnDungeonRoutes' => $hasOwnDungeonRoutes,
            'mayAddDungeonRoutes' => $mayAddDungeonRoutes,
        ])

        <h2 class="h4">{{ __('view_collection.edit.details') }}</h2>
        @include('common.collection.details', [
            'dungeonRouteCollection' => $dungeonRouteCollection,
            'editSections' => $editSections,
            'hasOwnDungeonRoutes' => $hasOwnDungeonRoutes,
            'selectedDungeonRouteIds' => $selectedDungeonRouteIds,
            'selectedGameVersion' => $selectedGameVersion,
            'selectedSeason' => $selectedSeason,
            'ownDungeonRoutes' => $ownDungeonRoutes,
            'teams' => $teams,
            'categories' => $categories,
        ])
    </div>
@endsection
