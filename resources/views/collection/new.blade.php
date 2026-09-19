<?php

use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\Team;
use App\Service\DungeonRoute\Dtos\DungeonRouteCollectionGroup;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, DungeonRouteCollectionGroup>    $editSections
 * @var bool                                            $hasOwnDungeonRoutes
 * @var GameVersion                                     $selectedGameVersion
 * @var Collection<int, Season>                         $seasons
 * @var Season|null                                     $selectedSeason
 * @var array<int, array{text: string, isWarning: bool}> $enemyForcesDetails
 * @var Collection<int, Team>                           $teams
 * @var Collection<int, DungeonRouteCollectionCategory> $categories
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_collection.new.title')])

@section('header-title', __('view_collection.new.header'))

@section('content')
    <div class="container">
        @include('common.collection.details', [
            'dungeonRouteCollection' => null,
            'editSections' => $editSections,
            'hasOwnDungeonRoutes' => $hasOwnDungeonRoutes,
            'selectedDungeonRouteIds' => [],
            'selectedGameVersion' => $selectedGameVersion,
            'seasons' => $seasons,
            'selectedSeason' => $selectedSeason,
            'enemyForcesDetails' => $enemyForcesDetails,
            'teams' => $teams,
            'categories' => $categories,
        ])
    </div>
@endsection
