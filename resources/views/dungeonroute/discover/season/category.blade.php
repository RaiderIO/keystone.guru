<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * @var AffixGroup               $currentAffixGroup
 * @var GameVersion              $gameVersion
 * @var Season                   $season
 * @var Collection<DungeonRoute> $dungeonroutes
 */

$title      ??= sprintf('%s routes', __($season->name));
$affixgroup ??= null;
?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-8 offset-xl-2',
    'disableDefaultRootClasses' => true,
    'breadcrumbsParams' => [$gameVersion, $season],
    'title' => $title
])

@include('common.general.inline', ['path' => 'dungeonroute/discover/discover'])

@section('scripts')
    @parent

    @include('common.handlebars.affixgroups')
@endsection

@section('content')
    @include('dungeonroute.discover.wallpaper', ['gameVersion' => $gameVersion])

    @include('dungeonroute.discover.panel', [
        'category' => $category,
        'gameVersion' => $gameVersion,
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
