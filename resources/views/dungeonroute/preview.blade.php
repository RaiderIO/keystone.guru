<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;

/**
 * @var DungeonRoute         $dungeonroute
 * @var Floor                $floor
 * @var float                $defaultZoom
 * @var string               $mapFacadeStyle
 * @var float|null           $killZonePathWeightMultiplier
 * @var array<string, mixed> $parameters
 */

$killZonePathWeightMultiplier ??= null;
?>

@extends('layouts.map', [
    'showAds' => false,
    'custom' => true,
    'footer' => false,
    'header' => false,
    'cookieConsent' => false,
    'title' => $dungeonroute->title,
    'analytics' => false,
])
@section('scripts')
    @parent

    <script>
        $(function () {
            // We need to fetch the enemies so the killzone polygon knows what to draw, but we don't want to display
            // the enemies themselves so hide those for displaying.
            dungeonMap.register('map:mapobjectgroupsloaded', null, function () {
                dungeonMap.mapObjectGroupManager.getByName('enemy').setVisibility(false);
            });
        });
    </script>
@endsection
@section('content')
    {{-- #app is min-height based (required for the sticky site header, #3851), which ends the
         percentage-height chain that used to size #map. Every other map view wraps its map in
         .wrapper, which carries a definite 100dvh of its own; without it #map collapses to 0 and
         the thumbnail render screenshots nothing but the page background (#4101). --}}
    <div class="wrapper">
        @include('common.maps.map', [
            'dungeon' => $dungeonroute->dungeon,
            'mappingVersion' => $dungeonroute->mappingVersion,
            'dungeonroute' => $dungeonroute,
            'showAds' => false,
            'edit' => false,
            'echo' => false,
            'noUI' => true,
            'killZonePathWeightMultiplier' => $killZonePathWeightMultiplier,
            'defaultZoom' => $defaultZoom,
            'mapFacadeStyle' => $mapFacadeStyle,
            'floor' => $floor,
            'showAttribution' => false,
            'zoomToContents' => false,
            'parameters' => $parameters,
            'hiddenMapObjectGroups' => [
                'enemyforcescheckpoint',
                'enemypatrol',
                'mountablearea',
                'floorunion',
                'floorunionarea',
            ],
        ])
    </div>
@endsection

