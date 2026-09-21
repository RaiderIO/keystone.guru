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
 * @var Collection<int, Team>                           $teams
 * @var Collection<int, DungeonRouteCollectionCategory> $categories
 */

// The routes section sits above the form it posts into, so its hidden inputs name that form
$formId = 'collection_details_form';
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_collection.new.title')])

@section('header-title', __('view_collection.new.header'))

@section('content')
    <div class="container">
        @include('common.collection.routes', [
            'dungeonRouteCollection' => null,
            'editSections' => $editSections,
            'hasOwnDungeonRoutes' => $hasOwnDungeonRoutes,
            'mayAddDungeonRoutes' => true,
            'selectedGameVersion' => $selectedGameVersion,
            'selectedSeason' => $selectedSeason,
            'formId' => $formId,
        ])

        <h2 class="h4">{{ __('view_collection.new.details') }}</h2>
        @include('common.collection.details', [
            'dungeonRouteCollection' => null,
            'formId' => $formId,
            'selectedGameVersion' => $selectedGameVersion,
            'seasons' => $seasons,
            'selectedSeason' => $selectedSeason,
            'teams' => $teams,
            'categories' => $categories,
        ])
    </div>
@endsection
