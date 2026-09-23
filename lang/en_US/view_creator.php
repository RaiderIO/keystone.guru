<?php

return [
    'directory' => [
        'title'                   => 'Route creators',
        'header'                  => 'Route creators',
        'description'             => 'Browse the people making routes on Keystone.guru. Creators with at least :min published routes are listed automatically - open a profile to see their pinned routes and where else to find them.',
        'search_label'            => 'Search for a creator',
        'search_placeholder'      => 'Search by name',
        'search_submit'           => 'Search',
        'category_label'          => 'Makes routes for',
        'category_any'            => 'Everyone',
        'category_option'         => ':category players',
        'sort_label'              => 'Sort by',
        'sort_active_this_season' => 'Active this season',
        'sort_most_routes'        => 'Most routes',
        'empty'                   => 'There are no listed creators yet.',
        'empty_for_category'      => 'No creators are sharing a ":category" collection yet.',
        'empty_for_search'        => 'No creators found matching ":search".',
    ],
    'featured' => [
        'title'         => 'Featured creators',
        'title_dungeon' => 'Creators for :dungeon',
        'see_all'       => 'See all creators',
        /** Tooltip on a rail entry - it carries the name because the name itself may be clipped to an ellipsis */
        'entry_title'         => ':name - :routes',
        'route_count'         => '{1} :count route|[2,*] :count routes',
        'dungeon_route_count' => '{1} :count :dungeon route|[2,*] :count :dungeon routes',
    ],
    'stats' => [
        'route_count'              => '{0} No routes|{1} :count route|[2,*] :count routes',
        'route_count_total'        => '{0} No published routes|{1} :count route total|[2,*] :count routes total',
        'season_route_count'       => '{1} :count route this season|[2,*] :count routes this season',
        'season_route_count_named' => '{0} No routes in :season|{1} :count route in :season|[2,*] :count routes in :season',
        'no_routes_this_season'    => 'No routes this season',
        'views'                    => '{0} No views|{1} :views view|[2,*] :views views',
        'rating'                   => '{1} ★ :rating (:count rating)|[2,*] ★ :rating (:count ratings)',
        'last_published'           => 'Last published :time',
    ],
];
