<?php

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\User;

/**
 * @var Dungeon      $dungeon
 * @var DungeonRoute $dungeonroute
 * @var Floor        $floor
 * @var array        $embedOptions
 * @var array        $parameters
 */

$affixes         = $dungeonroute->affixes->pluck('text', 'id');
$selectedAffixes = $dungeonroute->affixes->pluck('id');
if (count($affixes) == 0) {
    $affixes         = [-1 => __('view_dungeonroute.embed.any')];
    $selectedAffixes = -1;
}
$useFacade       = User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE;
$showEmbedHeader = !isset($embedOptions['show']['header']) || $embedOptions['show']['header'];

if ($dungeon->floorsForMapFacade($dungeonroute->mappingVersion, $useFacade)->active()->count() === 1) {
    $embedOptions['show']['floorSelection'] = false;
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
    @if($showEmbedHeader)
        @include(sprintf('dungeonroute.embedheaderstyle.%s', $embedOptions['style']), [
            'dungeonRoute' => $dungeonroute,
            'dungeon' => $dungeon,
            'floor' => $floor,
            'embedOptions' => $embedOptions,
        ])
    @endif

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
            'floor' => $floor,
            'showAttribution' => false,
            'parameters' => $parameters,
            'hiddenMapObjectGroups' => [
                'mountablearea',
                'floorunion',
                'floorunionarea',
            ],
            'show' => [
                'header' => false,
                'share' => [],
                'controls' => [
                    'enemyInfo' => $embedOptions['show']['enemyInfo'],
                    'enemyForces' => !$showEmbedHeader ? false : $embedOptions['show']['enemyForces'],
                    'pullsDefaultState' => $embedOptions['pullsDefaultState'],
                    'pullsHideOnMove' => $embedOptions['pullsHideOnMove'],
                    'pulls' => $embedOptions['show']['pulls'],
                ],
            ],
        ])
    </div>
@endsection
