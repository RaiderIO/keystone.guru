<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * @var AffixGroup $currentAffixGroup
 * @var Season $season
 * @var Collection<DungeonRoute> $dungeonroutes
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
