<?php

return [
    'published_state_subtext' => [
        'unpublished'     => 'Only you may view this collection',
        'team'            => 'Only members of the team chosen below may view this collection',
        'world_with_link' => 'Anyone with the link may view this collection',
        'world'           => 'Anyone may view this collection',
    ],
    'kind' => [
        'season_set' => ':season set · :covered/:total dungeons',
        'free_form'  => '{0} :game_version · no dungeons|{1} :game_version · :count dungeon|[2,*] :game_version · :count dungeons',
    ],
    'index' => [
        'title'                   => 'My collections',
        'header'                  => 'My collections',
        'description'             => 'A collection is a shareable list of your routes, for example all your routes for this week.',
        'create_collection'       => 'New collection',
        'no_collections'          => 'You have not created any collections yet.',
        'no_category'             => 'No category',
        'table_header_name'       => 'Name',
        'table_header_category'   => 'Category',
        'table_header_visibility' => 'Visible to',
        'table_header_routes'     => 'Routes',
        'table_header_kind'       => 'Covers',
        'view'                    => 'View',
        'published_state'         => [
            'unpublished'     => 'Unpublished',
            'team'            => 'Team only',
            'world'           => 'Public',
            'world_with_link' => 'Public with link',
        ],
        'filter_game_version'     => 'Game version',
        'filter_season'           => 'Season',
        'filter_season_all'       => 'All',
        'filter_season_none'      => 'No season',
        'filter_submit'           => 'Filter',
        'no_collections_filtered' => 'You have no collections for this game version and season.',
    ],
    'new' => [
        'title'  => 'New collection',
        'header' => 'New collection',
    ],
    'edit' => [
        'title'           => 'Edit %s',
        'view_collection' => 'View collection',
        'details'         => 'Details',
    ],
    'view' => [
        'title'       => '%s',
        'by_author'   => 'A collection by :author',
        'route_count' => '{0} No routes|{1} :count route|[2,*] :count routes',
        'no_routes'   => 'This collection does not contain any routes that you may view.',
        'slot_empty'  => 'No route for :dungeon yet.',
    ],
];
