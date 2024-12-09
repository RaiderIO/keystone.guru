@extends('layouts.map', ['custom' => true, 'footer' => false, 'header' => false, 'title' => $dungeonroute->title, 'cookieConsent' => $dungeonroute->demo === 1 ? false : null ])
<?php

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\LiveSession;

/**
 * @var DungeonRoute $dungeonroute
 * @var LiveSession  $livesession
 * @var Floor        $floor
 */

$affixes         = $dungeonroute->affixes->pluck('text', 'id');
$selectedAffixes = $dungeonroute->affixes->pluck('id');
if (count($affixes) == 0) {
    $affixes         = [-1 => __('view_dungeonroute.livesession.view.any')];
    $selectedAffixes = -1;
}

$dungeon = Dungeon::findOrFail($dungeonroute->dungeon_id);
?>
@section('scripts')
    @parent

    @include('common.handlebars.affixgroupsselect', ['affixgroups' => $dungeonroute->affixes])

@endsection

@include('common.general.inline', [
    'path' => 'dungeonroute/livesession',
    'dependencies' => ['common/maps/map'],
])

@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'dungeonroute' => $dungeonroute,
            'mappingVersion' => $dungeonroute->mappingVersion,
            'livesession' => $livesession,
            'edit' => false,
            'floorId' => $floor->id,
            'show' => [
                'header' => true,
                'controls' => [
                    'live' => true,
                    'pulls' => true,
                    'enemyInfo' => true,
                ],
                'share' => [
                    'link' => !$dungeonroute->isSandbox(),
                    'embed' => !$dungeonroute->isSandbox(),
                    'mdt-export' => $dungeon->mdt_supported,
                    'publish' => false,
                ],
            ],
            'hiddenMapObjectGroups' => [
                'floorunion',
                'floorunionarea',
            ],
        ])
    </div>
@endsection

