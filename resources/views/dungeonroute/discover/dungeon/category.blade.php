<?php
/**
 * @var AffixGroup               $currentAffixGroup
 * @var Dungeon                  $dungeon
 * @var Collection<DungeonRoute> $dungeonroutes
 */

use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

$title      ??= sprintf('%s routes', __($dungeon->name));
$affixgroup ??= null;
?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-8 offset-xl-2',
    'disableDefaultRootClasses' => true,
    'breadcrumbsParams' => [$dungeon],
    'title' => $title,
])

@include('common.general.inline', ['path' => 'dungeonroute/discover/discover',
        'options' =>  [
        ],
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
