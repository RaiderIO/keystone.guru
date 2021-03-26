<?php
$dungeon = $dungeon ?? null;
?>
@include('common.general.inline', ['path' => 'dungeonroute/discover/searchloadmore', 'options' => [
        'category' => $category,
        'dungeon' => $dungeon,
        'routeContainerListSelector' => $routeListContainerSelector,
        'routeLoadMoreSelector' => '#category_route_load_more',
        'routeLoaderSelector' => '#category_route_load_more_loader',
        'loadMoreCount' => config('keystoneguru.discover.loadmore.count'),
    ]
])

<div id="category_route_load_more">

</div>

<div id="category_route_load_more_loader" class="text-center">
    <i class="fas fa-stroopwafel fa-spin"></i> {{ __('Loading...') }}
</div>
