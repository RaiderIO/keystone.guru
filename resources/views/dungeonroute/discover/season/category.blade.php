<?php
/**
 * @var $currentAffixGroup \App\Models\AffixGroup\AffixGroup
 * @var $season \App\Models\Season
 * @var $dungeonroutes \App\Models\DungeonRoute[]|\Illuminate\Support\Collection
 */
$title      = $title ?? sprintf('%s routes', __($season->name));
$affixgroup = $affixgroup ?? null;
?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-10 offset-xl-1',
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
        'loadMore' => $dungeonroutes->count() >= config('keystoneguru.discover.limits.category'),
    ])

    @component('common.general.modal', ['id' => 'userreport_dungeonroute_modal'])
        @include('common.modal.userreport.dungeonroute')
    @endcomponent
@endsection
