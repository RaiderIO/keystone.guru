@extends('layouts.map', ['custom' => true, 'footer' => false, 'header' => false, 'title' => $dungeonroute->title, 'cookieConsent' => $dungeonroute->demo === 1 ? false : null ])
<?php
/** @var $dungeonroute \App\Models\DungeonRoute */
/** @var $livesession \App\Models\LiveSession */
/** @var $floor \App\Models\Floor */

$affixes = $dungeonroute->affixes->pluck('text', 'id');
$selectedAffixes = $dungeonroute->affixes->pluck('id');
if (count($affixes) == 0) {
    $affixes         = [-1 => __('views/dungeonroute.livesession.view.any')];
    $selectedAffixes = -1;
}
$dungeon = \App\Models\Dungeon::findOrFail($dungeonroute->dungeon_id);
?>
@section('scripts')
    @parent

    @include('common.handlebars.affixgroupsselect', ['affixgroups' => $dungeonroute->affixes])

@endsection

@include('common.general.inline', [
    'path' => 'dungeonroute/livesession',
    'dependencies' => ['common/maps/map']
])

@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'dungeonroute' => $dungeonroute,
            'livesession' => $livesession,
            'edit' => false,
            'floorId' => $floor->id,
            'show' => [
                'header' => true,
                'controls' => [
                    'live' => true,
                    'pulls' => true,
                    'enemyinfo' => true,
                ],
                'share' => [
                    'link' => !$dungeonroute->isSandbox(),
                    'embed' => !$dungeonroute->isSandbox(),
                    'mdt-export' => true,
                    'publish' => false,
                ]
            ],
            'hiddenMapObjectGroups' => [],
        ])
    </div>
@endsection

