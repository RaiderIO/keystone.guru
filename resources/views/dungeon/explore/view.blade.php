<?php
/**
 * @var $dungeon \App\Models\Dungeon
 * @var $floor \App\Models\Floor\Floor
 * @var $title string
 * @var $mapContext \App\Logic\MapContext\MapContext
 */
?>
@extends('layouts.map', ['custom' => true, 'footer' => false, 'header' => false, 'title' => $title])
@section('linkpreview')
    <?php
    $defaultDescription = sprintf(__('views/dungeonroute.view.linkpreview_default_description_explore'), __($dungeon->name))
    ?>
    @include('common.general.linkpreview', [
        'title' => sprintf(__('views/dungeonroute.view.linkpreview_title'), $title),
        'description' => $defaultDescription,
        'image' => $dungeon->getImageUrl()
    ])
@endsection

@section('content')
    <div class="wrapper">
        @include('common.maps.map', [
            'dungeon' => $dungeon,
            'floor' => $floor,
            'edit' => false,
            'echo' => false,
            'mapContext' => $mapContext,
            'show' => [
                'header' => true,
                'controls' => [
                    'view' => true,
                    'pulls' => false,
                    'enemyInfo' => true,
                ],
            ],
            'hiddenMapObjectGroups' => [
                'brushline',
                'path',
                'killzone',
                'killzonepath',
                'floorunion',
                'floorunionarea'
            ],
        ])
    </div>
@endsection

