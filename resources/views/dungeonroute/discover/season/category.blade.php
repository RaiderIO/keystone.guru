<?php
/**
 * @var $currentAffixGroup \App\Models\AffixGroup\AffixGroup
 * @var $season \App\Models\Season
 * @var $dungeonroutes \App\Models\DungeonRoute\DungeonRoute[]|\Illuminate\Support\Collection
 */
$title      ??= sprintf('%s routes', __($season->name));
$affixgroup ??= null;
?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-8 offset-xl-2',
    'disableDefaultRootClasses' => true,
    'breadcrumbsParams' => [$expansion, $season],
    'title' => $title
])

@include('common.general.inline', ['path' => 'dungeonroute/discover/discover',
        'options' =>  [
        ]
])

@section('content')
    @include('dungeonroute.discover.wallpaper', ['expansion' => $expansion])

    @include('dungeonroute.discover.panel', [
        'category' => $category,
        'expansion' => $expansion,
        'season' => $season,
        'title' => $title,
        'currentAffixGroup' => $currentAffixGroup,
        'affixgroup' => $affixgroup,
        'dungeonroutes' => $dungeonroutes,
        'showDungeonImage' => true,
        'loadMore' => $dungeonroutes->count() >= config('keystoneguru.discover.limits.category'),
    ])

    @component('common.general.modal', ['id' => 'userreport_dungeonroute_modal'])
        @include('common.modal.userreport.dungeonroute')
    @endcomponent
@endsection
