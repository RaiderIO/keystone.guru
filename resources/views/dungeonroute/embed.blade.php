<?php

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;

/**
 * @var DungeonRoute $dungeonroute
 * @var Floor        $floor
 * @var array        $embedOptions
 * @var array        $parameters
 */

$dungeon = Dungeon::findOrFail($dungeonroute->dungeon_id)->load(['expansion', 'floors']);

$affixes         = $dungeonroute->affixes->pluck('text', 'id');
$selectedAffixes = $dungeonroute->affixes->pluck('id');
if (count($affixes) == 0) {
    $affixes         = [-1 => __('view_dungeonroute.embed.any')];
    $selectedAffixes = -1;
}
?>

@extends('layouts.map', [
    'showAds' => false,
    'custom' => true,
    'footer' => false,
    'header' => false,
    'title' => __('view_dungeonroute.embed.title', ['routeTitle' => $dungeonroute->title]),
    'bodyClass' => 'overflow-hidden',
    'cookieConsent' => false,
])

@section('scripts')
    @parent

    @include('common.handlebars.affixgroupsselect', ['affixgroups' => $dungeonroute->affixes])
@endsection

@include('common.general.inline', [
    'path' => 'dungeonroute/edit',
    'dependencies' => ['common/maps/map'],
])

@include('common.general.inline', ['path' => 'common/maps/embedtopbar', 'options' => [
    'dependencies' => ['common/maps/map'],
    'switchDungeonFloorSelect' => '#map_floor_selection_dropdown',
    'defaultSelectedFloorId' => $floor->id,
    'mdtStringCopyEnabled' => $dungeon->mdt_supported,
]])

@section('content')
    @include(sprintf('dungeonroute.embedheaderstyle.%s', $embedOptions['style']), [
        'dungeonRoute' => $dungeonroute,
        'dungeon' => $dungeon,
        'floor' => $floor,
        'embedOptions' => $embedOptions,
    ])

    <div class="wrapper embed_wrapper {{ $embedOptions['style'] }}">
        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'mappingVersion' => $dungeonroute->mappingVersion,
            'dungeonroute' => $dungeonroute,
            'mapBackgroundColor' => $embedOptions['mapBackgroundColor'],
            'embed' => true,
            'embedStyle' => $embedOptions['style'],
            'edit' => false,
            'echo' => false,
            'defaultZoom' => 1,
            'floorId' => $floor->id,
            'showAttribution' => false,
            'parameters' => $parameters,
            'hiddenMapObjectGroups' => [
                'enemypack',
                'mountablearea',
                'floorunion',
                'floorunionarea',
            ],
            'show' => [
                'header' => false,
                'share' => [],
                'controls' => [
                    'enemyInfo' => $embedOptions['show']['enemyInfo'],
                    'enemyForces' => $embedOptions['show']['enemyForces'],
                    'pullsDefaultState' => $embedOptions['pullsDefaultState'],
                    'pullsHideOnMove' => $embedOptions['pullsHideOnMove'],
                    'pulls' => $embedOptions['show']['pulls'],
                ],
            ],
        ])
    </div>
@endsection
