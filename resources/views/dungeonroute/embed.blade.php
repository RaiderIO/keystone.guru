@extends('layouts.map', [
    'showAds' => false,
    'custom' => true,
    'footer' => false,
    'header' => false,
    'title' => __('views/dungeonroute.embed.title', ['routeTitle' => $dungeonroute->title]),
    'bodyClass' => 'overflow-hidden',
    'cookieConsent' => false,
])
<?php
/** @var $dungeonroute \App\Models\DungeonRoute */
/** @var $floor \App\Models\Floor\Floor */
/** @var $embedOptions array */
$dungeon = \App\Models\Dungeon::findOrFail($dungeonroute->dungeon_id)->load(['expansion', 'floors']);

$affixes         = $dungeonroute->affixes->pluck('text', 'id');
$selectedAffixes = $dungeonroute->affixes->pluck('id');
if (count($affixes) == 0) {
    $affixes         = [-1 => __('views/dungeonroute.embed.any')];
    $selectedAffixes = -1;
}
?>
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
            'dungeonroute' => $dungeonroute,
            'mapBackgroundColor' => $embedOptions['mapBackgroundColor'],
            'embed' => true,
            'embedStyle' => $embedOptions['style'],
            'edit' => false,
            'echo' => false,
            'defaultZoom' => 1,
            'floorId' => $floor->id,
            'showAttribution' => false,
            'hiddenMapObjectGroups' => [
                'enemypack',
                'mountablearea',
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
