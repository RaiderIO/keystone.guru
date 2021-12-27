<?php
/**
 * @var $currentAffixGroup \App\Models\AffixGroup\AffixGroup
 * @var $dungeon \App\Models\Dungeon
 * @var $dungeonroutes \App\Models\DungeonRoute[]|\Illuminate\Support\Collection
 */
$title      = $title ?? sprintf('%s routes', __($dungeon->name));
$affixgroup = $affixgroup ?? null;
?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-10 offset-xl-1',
    'disableDefaultRootClasses' => true,
    'breadcrumbsParams' => [$dungeon],
    'title' => $title
])

@include('common.general.inline', ['path' => 'dungeonroute/discover/discover',
        'options' =>  [
        ]
])

@section('content')
    @include('dungeonroute.discover.wallpaper', ['dungeon' => $dungeon])

    @include('dungeonroute.discover.panel', [
        'category' => $category,
        'expansion' => $expansion,
        'dungeon' => $dungeon,
        'title' => $title,
        'currentAffixGroup' => $currentAffixGroup,
        'affixgroup' => $affixgroup,
        'dungeonroutes' => $dungeonroutes,
        'loadMore' => $dungeonroutes->count() >= config('keystoneguru.discover.limits.category'),
    ])

    @component('common.general.modal', ['id' => 'userreport_dungeonroute_modal'])
        @include('common.modal.userreport.dungeonroute')
    @endcomponent
@endsection
