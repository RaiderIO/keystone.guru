<?php
/**
 * @var $category string
 * @var $currentAffixGroup \App\Models\AffixGroup\AffixGroup
 * @var $dungeonroutes \App\Models\DungeonRoute[]|\Illuminate\Support\Collection
 */
$affixgroup = $affixgroup ?? null;
?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-10 offset-xl-1',
    'disableDefaultRootClasses' => true,
    'title' => $title,
    'breadcrumbsParams' => [$expansion]
])

@include('common.general.inline', ['path' => 'dungeonroute/discover/discover'])

@section('content')
    @include('dungeonroute.discover.wallpaper', ['expansion' => $expansion])

    @include('dungeonroute.discover.panel', [
        'expansion' => $expansion,
        'cols' => 2,
        'category' => $category,
        'title' => $title,
        'dungeonroutes' => $dungeonroutes,
        'currentAffixGroup' => $currentAffixGroup,
        'affixgroup' => $affixgroup,
        'showDungeonImage' => true,
        'loadMore' => $dungeonroutes->count() >= config('keystoneguru.discover.limits.category'),
    ])

    @component('common.general.modal', ['id' => 'userreport_dungeonroute_modal'])
        @include('common.modal.userreport.dungeonroute')
    @endcomponent
@endsection
