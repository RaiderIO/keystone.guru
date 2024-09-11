<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;

/**
 * @var DungeonRoute $dungeonroute
 * @var Floor        $floor
 * @var array        $parameters
 */

$affixes         = $dungeonroute->affixes->pluck('text', 'id');
$selectedAffixes = $dungeonroute->affixes->pluck('id');
if (count($affixes) == 0) {
    $affixes         = [-1 => __('view_dungeonroute.view.any')];
    $selectedAffixes = -1;
}
?>
@extends('layouts.map', [
    'custom' => true,
    'footer' => false,
    'header' => false,
    'title' => $dungeonroute->title,
    'cookieConsent' => $dungeonroute->demo === 1 ? false : null,
])

@section('scripts')
    @parent

    @include('common.handlebars.affixgroupsselect', ['affixgroups' => $dungeonroute->affixes])

@endsection

@section('linkpreview')
    <?php
    $defaultDescription = $dungeonroute->author === null ?
        sprintf(__('view_dungeonroute.view.linkpreview_default_description_sandbox'), __($dungeonroute->dungeon->name))
        : sprintf(__('view_dungeonroute.view.linkpreview_default_description'), __($dungeonroute->dungeon->name), $dungeonroute->author?->name);
    ?>
    @include('common.general.linkpreview', [
        'title' => sprintf(__('view_dungeonroute.view.linkpreview_title'), $dungeonroute->title),
        'description' => empty($dungeonroute->description) ? $defaultDescription : $dungeonroute->description,
        'image' => $dungeonroute->dungeon->getImageUrl(),
    ])
@endsection

@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'dungeon' => $dungeonroute->dungeon,
            'mappingVersion' => $dungeonroute->mappingVersion,
            'dungeonroute' => $dungeonroute,
            'edit' => false,
            'floorId' => $floor->id,
            'noUI' => (bool)$dungeonroute->demo,
            'gestureHandling' => (bool)$dungeonroute->demo,
            'showAttribution' => !(bool)$dungeonroute->demo,
            'parameters'   => $parameters,
            'show' => [
                'header' => true,
                'controls' => [
                    'view' => true,
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

