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
 * @var Collection<int, GameVersion>                    $gameVersions
 * @var GameVersion                                     $selectedGameVersion
 * @var Collection<int, Collection<int, Season>>        $seasonsPerGameVersion
 * @var Season|null                                     $selectedSeason
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
            'gameVersions' => $gameVersions,
            'selectedGameVersion' => $selectedGameVersion,
            'seasonsPerGameVersion' => $seasonsPerGameVersion,
            'selectedSeason' => $selectedSeason,
            'teams' => $teams,
            'categories' => $categories,
        ])
    </div>
@endsection

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            let $gameVersion = $('#game_version_id');
            let initialKind = null;

            // The route picker is built for one game version and season; once either changes it would offer the wrong routes
            function applyKind() {
                let gameVersionId = $gameVersion.val();
                $('.collection_season').each(function () {
                    let isSelected = String($(this).data('game-version-id')) === gameVersionId;
                    $(this).prop('hidden', !isSelected).find('select').prop('disabled', !isSelected);
                });

                let kind = `${gameVersionId}-${$('.collection_season:not([hidden]) select').val() ?? ''}`;
                initialKind ??= kind;
                $('#collection_dungeon_routes').prop('hidden', kind !== initialKind).find('input').prop('disabled', kind !== initialKind);
                $('#collection_dungeon_routes_kind_changed').prop('hidden', kind === initialKind);
            }

            $gameVersion.on('change', applyKind);
            $('.collection_season select').on('change', applyKind);
            applyKind();
        });
    </script>
@endsection
