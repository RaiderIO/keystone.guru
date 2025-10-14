<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use Illuminate\Support\Collection;

/**
 * @var GameVersion              $gameVersion
 * @var string                   $title
 * @var int                      $cols
 * @var Collection<DungeonRoute> $dungeonroutes
 * @var AffixGroup               $currentAffixGroup
 */

$dungeon          ??= null;
$cols             ??= 4;
$showMore         ??= false;
$loadMore         ??= false;
$showDungeonImage ??= false;
$affixgroup       ??= null;
$cache            ??= true;
?>
<div class="discover_panel">
    <div class="row mt-4">
        <div class="col-xl">
            <h2 class="text-center">
                @isset($link)
                    <a href="{{ $link }}">
                        {{ $title }}
                    </a>
                @else
                    {{ $title }}
                @endisset
            </h2>
            @if($affixgroup !== null)
                <div class="row mb-2">
                    <div class="offset-2">
                    </div>
                    <div class="col-8">
                        @include('common.affixgroup.affixgroup', ['affixgroup' => $affixgroup, 'cols' => 1, 'center' => true, 'isFirst' => true])
                    </div>
                </div>
            @endisset
            <div id="category_route_list">
                @include('common.dungeonroute.cardlist', [
                    'cols' => $cols,
                    'currentAffixGroup' => $currentAffixGroup,
                    'affixgroup' => $affixgroup,
                    'dungeonroutes' => $dungeonroutes,
                    'showDungeonImage' => $showDungeonImage,
                    'cache' => $cache,
                ])
            </div>
        </div>
    </div>

    @if($showMore)
        <div class="row mt-4">
            <div class="col-xl text-center">
                <a href="{{ $link }}">
                    >> {{ __('view_dungeonroute.discover.panel.show_more') }}
                </a>
            </div>
        </div>
    @endif

    @if($loadMore)
        @include('common.search.loadmore', [
            'category' => $category,
            'gameVersion' => $gameVersion,
            'dungeon' => $dungeon,
            'routeListContainerSelector' => '#category_route_list',
        ])
    @endif
</div>
