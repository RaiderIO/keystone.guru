<?php
/**
 * @var AffixGroup                    $currentAffixGroup
 * @var GameVersion                   $gameVersion
 * @var Dungeon                       $dungeon
 * @var Collection<int, DungeonRoute> $dungeonroutes
 * @var string                        $category
 * @var int                           $page
 * @var int                           $perPage
 * @var bool                          $hasMore
 */

use App\Features\DungeonRouteListRework;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;

$title      ??= sprintf('%s routes', __($dungeon->name));
$affixgroup ??= null;
$page       ??= 1;
$perPage    ??= config('keystoneguru.discover.limits.category');
$hasMore    ??= false;
?>


@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-8 offset-xl-2',
    'disableDefaultRootClasses' => true,
    'breadcrumbsParams' => [$gameVersion, $dungeon],
    'title' => $title,
    'dungeonContextLinks' => $gameVersionDungeons->mapWithKeys(fn (Dungeon $dungeon) => [
        $dungeon->key => route(sprintf('dungeonroutes.discoverdungeon.%s', $category), [
            'gameVersion' => $gameVersion,
            'dungeon' => $dungeon,
        ])
    ]),
])

@include('common.general.inline', ['path' => 'dungeonroute/discover/discover'])

@section('scripts')
    @parent

    @include('common.handlebars.affixgroups')
@endsection

@section('content')
    @include('dungeonroute.discover.wallpaper', ['dungeon' => $dungeon])

    @if(Feature::active(DungeonRouteListRework::class))
        <?php $startRank = ($page - 1) * $perPage + 1; ?>
        <div class="row mt-4 align-items-center discover_section_header">
            <div class="col">
                <h5 class="mb-0 text-center">{{ $title }}</h5>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col">
                @include('common.dungeonroute.leaderboard', [
                    'dungeonroutes' => $dungeonroutes,
                    'startRank' => $startRank,
                    'cache' => true,
                ])
            </div>
        </div>

        @include('dungeonroute.discover.pagination', [
            'page' => $page,
            'hasMore' => $hasMore,
        ])
    @else
        @include('dungeonroute.discover.panel', [
            'category' => $category,
            'gameVersion' => $gameVersion,
            'dungeon' => $dungeon,
            'title' => $title,
            'currentAffixGroup' => $currentAffixGroup,
            'affixgroup' => $affixgroup,
            'dungeonroutes' => $dungeonroutes,
            'loadMore' => $dungeonroutes->count() >= config('keystoneguru.discover.limits.category'),
            'loadMoreOffset' => config('keystoneguru.discover.limits.category'),
        ])
    @endif

    @component('common.general.modal', ['id' => 'userreport_dungeonroute_modal'])
        @include('common.modal.userreport.dungeonroute')
    @endcomponent
@endsection
