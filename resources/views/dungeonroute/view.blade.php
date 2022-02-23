@extends('layouts.map', ['custom' => true, 'footer' => false, 'header' => false, 'title' => $dungeonroute->title, 'cookieConsent' => $dungeonroute->demo === 1 ? false : null ])
<?php
/** @var $dungeonroute \App\Models\DungeonRoute */
/** @var $floor \App\Models\Floor */

$affixes = $dungeonroute->affixes->pluck('text', 'id');
$selectedAffixes = $dungeonroute->affixes->pluck('id');
if (count($affixes) == 0) {
    $affixes         = [-1 => __('views/dungeonroute.view.any')];
    $selectedAffixes = -1;
}
$dungeon = \App\Models\Dungeon::findOrFail($dungeonroute->dungeon_id);
?>
@section('scripts')
    @parent

    @include('common.handlebars.affixgroupsselect', ['affixgroups' => $dungeonroute->affixes])

@endsection
@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'dungeonroute' => $dungeonroute,
            'edit' => false,
            'floorId' => $floor->id,
            'noUI' => (bool)$dungeonroute->demo,
            'gestureHandling' => (bool)$dungeonroute->demo,
            'showAttribution' => !(bool)$dungeonroute->demo,
            'show' => [
                'header' => true,
                'controls' => [
                    'view' => true,
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

