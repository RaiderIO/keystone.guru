@extends('layouts.map', ['custom' => true, 'footer' => false, 'header' => false, 'title' => $model->title, 'cookieConsent' => $model->demo === 1 ? false : null ])
<?php
/** @var $model \App\Models\DungeonRoute */
/** @var $floor \App\Models\Floor */

$affixes = $model->affixes->pluck('text', 'id');
$selectedAffixes = $model->affixes->pluck('id');
if (count($affixes) == 0) {
    $affixes = [-1 => 'Any'];
    $selectedAffixes = -1;
}
$dungeon = \App\Models\Dungeon::findOrFail($model->dungeon_id);
?>
@section('scripts')
    @parent

    @include('common.handlebars.affixgroupsselect', ['affixgroups' => $model->affixes])

@endsection
@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'dungeonroute' => $model,
            'edit' => false,
            'floorId' => $floor->id,
            'noUI' => (bool)$model->demo,
            'gestureHandling' => (bool)$model->demo,
            'showAttribution' => !(bool)$model->demo,
            'show' => [
                'share' => [
                    'link' => !$model->isSandbox(),
                    'embed' => !$model->isSandbox(),
                    'mdt-export' => true,
                    'publish' => false,
                ]
            ],
            'hiddenMapObjectGroups' => [
                'killzonepath'
            ],
        ])
    </div>
@endsection

