<?php

use App\Models\DungeonRoute\DungeonRouteCollection;

/**
 * What a collection covers: "Season 2 set · 3/8 dungeons", or "Retail · 4 dungeons" for a free-form one. Only someone
 * who may edit the collection sees a season set counted against its pool.
 *
 * @var DungeonRouteCollection $dungeonRouteCollection Expects its season with dungeons and its game version loaded.
 * @var int                    $coveredDungeonCount
 */
?>
@if($dungeonRouteCollection->isSeasonSet() && $dungeonRouteCollection->season !== null)
    @can('edit', $dungeonRouteCollection)
        {{ __('view_collection.kind.season_set', [
            'season' => $dungeonRouteCollection->season->name,
            'covered' => $coveredDungeonCount,
            'total' => $dungeonRouteCollection->season->dungeons->count(),
        ]) }}
    @else
        {{ trans_choice('view_collection.kind.season_set_public', $coveredDungeonCount, [
            'season' => $dungeonRouteCollection->season->name,
            'count' => $coveredDungeonCount,
        ]) }}
    @endcan
@else
    {{ trans_choice('view_collection.kind.free_form', $coveredDungeonCount, [
        'game_version' => __($dungeonRouteCollection->gameVersion->name),
        'count' => $coveredDungeonCount,
    ]) }}
@endif
