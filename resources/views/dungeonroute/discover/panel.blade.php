<?php
/** @var $title string */
/** @var $cols int */
/** @var $dungeonroutes \App\Models\DungeonRoute[]|\Illuminate\Support\Collection */

$dungeon = $dungeon ?? null;
$cols = $cols ?? 2;
$showMore = $showMore ?? false;
$loadMore = $loadMore ?? false;
$showDungeonImage = $showDungeonImage ?? false;
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
            @isset($affixgroup)
                <div class="row mb-2">
                    <div class="offset-2">
                    </div>
                    <div class="col-8">
                        @include('common.affixgroup.affixgroup', ['affixGroup' => $affixgroup])
                    </div>
                </div>
            @endisset
            <div id="category_route_list">
                @include('common.dungeonroute.cardlist', ['cols' => $cols, 'dungeonroutes' => $dungeonroutes, 'showDungeonImage' => $showDungeonImage])
            </div>
        </div>
    </div>
    @if($showMore)
        <div class="row mt-4">
            <div class="col-xl text-center">
                <a href="{{ $link }}">
                    {{ __('>> Show more') }}
                </a>
            </div>
        </div>
    @endif

    @if($loadMore)
        @include('common.dungeonroute.search.loadmore', ['category' => $category, 'dungeon' => $dungeon, 'routeListContainerSelector' => '#category_route_list'])
    @endif
</div>