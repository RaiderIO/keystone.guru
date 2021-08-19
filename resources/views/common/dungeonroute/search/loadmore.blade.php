<?php
$dungeon = $dungeon ?? null;
$hasLoader = isset($routeLoaderSelector);
$routeLoaderSelector = $routeLoaderSelector ?? '#category_route_load_more_loader';
?>
@include('common.general.inline', ['path' => 'dungeonroute/discover/searchloadmore', 'options' => [
        'category' => $category,
        'dungeon' => $dungeon,
        'routeContainerListSelector' => $routeListContainerSelector,
        'routeLoadMoreSelector' => '#category_route_load_more',
        'routeLoaderSelector' => $routeLoaderSelector,
        'loadMoreCount' => config('keystoneguru.discover.loadmore.count'),
    ]
])

<div id="category_route_load_more">

</div>

@if(!$hasLoader)
    <div id="category_route_load_more_loader" class="text-center">
        <i class="fas fa-stroopwafel fa-spin"></i> {{ __('views/common.dungeonroute.search.loadmore.loading') }}
    </div>
@endif
