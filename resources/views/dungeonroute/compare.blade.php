<?php
/** @var $dungeon \App\Models\Dungeon */
/** @var $dungeonRoutes \Illuminate\Support\Collection|\App\Models\DungeonRoute[] */
/** @var $floor \App\Models\Floor */
/** @var $mappingVersion \App\Models\Mapping\MappingVersion */
?>
@extends('layouts.map', [
    'custom' => true,
    'footer' => false,
    'header' => false,
    'title' => __('views/dungeonroute.compare.title', ['count' => $dungeonRoutes->count()])
])

{{--@section('linkpreview')--}}
{{--    <?php--}}
{{--    $defaultDescription = $dungeonroute->author === null ?--}}
{{--        sprintf(__('views/dungeonroute.view.linkpreview_default_description_sandbox'), __($dungeonroute->dungeon->name))--}}
{{--        : sprintf(__('views/dungeonroute.view.linkpreview_default_description'), __($dungeonroute->dungeon->name), optional($dungeonroute->author)->name);--}}
{{--    ?>--}}
{{--    @include('common.general.linkpreview', [--}}
{{--        'title' => sprintf(__('views/dungeonroute.view.linkpreview_title'), $dungeonroute->title),--}}
{{--        'description' => empty($dungeonroute->description) ? $defaultDescription : $dungeonroute->description,--}}
{{--        'image' => $dungeonroute->dungeon->getImageUrl()--}}
{{--    ])--}}
{{--@endsection--}}

@section('content')
    <div class="wrapper">
        @include('common.maps.mapcompare', [
            'dungeon' => $dungeon,
            'dungeonRoutes' => $dungeonRoutes,
            'floor' => $floor,
            'mappingVersion' => $mappingVersion,
            'edit' => false,
            'noUI' => false,
            'gestureHandling' => false,
            'showAttribution' => true,
            'show' => [
                'header' => true,
                'controls' => [
                    'view' => false,
                    'pulls' => false,
                    'enemyInfo' => true,
                ],
                'share' => [
                    'link' => false,
                    'embed' => false,
                    'mdt-export' => false,
                    'publish' => false,
                ]
            ],
            'hiddenMapObjectGroups' => [],
        ])
    </div>
@endsection

