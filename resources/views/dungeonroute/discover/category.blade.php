<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

/**
 * @var string                   $category
 * @var AffixGroup               $currentAffixGroup
 * @var Collection<DungeonRoute> $dungeonroutes
 */

$affixgroup ??= null;
?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-8 offset-xl-2',
    'disableDefaultRootClasses' => true,
    'title' => $title,
    'breadcrumbsParams' => [$expansion],
])

@include('common.general.inline', ['path' => 'dungeonroute/discover/discover'])

@section('content')
    @include('dungeonroute.discover.wallpaper', ['expansion' => $expansion])

    @include('dungeonroute.discover.panel', [
        'expansion' => $expansion,
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
