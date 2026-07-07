<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use Illuminate\Support\Collection;

/**
 * @var GameVersion                   $gameVersion
 * @var string                        $title
 * @var string|null                   $link
 * @var int                           $cols
 * @var Collection<int, DungeonRoute> $dungeonroutes
 * @var AffixGroup                    $currentAffixGroup
 */

$dungeon          ??= null;
$cols             ??= 4;
$showMore         ??= false;
$loadMore         ??= false;
$loadMoreOffset   ??= 0;
$showDungeonImage ??= false;
$affixgroup       ??= null;
$cache            ??= true;
?>
<div class="discover_panel px-xl-2">
    <div class="row mt-4">
        <div class="col-xl">
            <h2 class="text-center">
                @isset($link)
                    <a href="{{ $link }}">
                        {{ $title }}
                        @if((parse_url($link)['host'] ?? '') !== parse_url(config('app.url'))['host'])
                            <i class="fas fa-external-link-alt"></i>
                        @endif
                    </a>
                @else
                    {{ $title }}
                @endisset
            </h2>
            @if($affixgroup !== null)
                <div class="row mb-2">
                    <div class="col-8 offset-2">
                        @include('common.affixgroup.affixgroup', [
                            'affixgroup' => $affixgroup,
                            'cols' => 1,
                            'center' => true,
                            'isFirst' => true
                        ])
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
            'offset' => $loadMoreOffset,
            'routeListContainerSelector' => '#category_route_list',
        ])
    @endif
</div>
