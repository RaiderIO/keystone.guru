<?php

return [
    'directory' => [
        'title'                   => 'Routen-Creator',
        'header'                  => 'Routen-Creator',
        'description'             => 'Entdecke die Leute, die auf Keystone.guru Routen erstellen. Creator mit veröffentlichten Routen werden automatisch aufgeführt - öffne ein Profil, um die angehefteten Routen zu sehen und wo du sie sonst noch findest.',
        'search_label'            => 'Nach einem Creator suchen',
        'search_placeholder'      => 'Nach Name suchen',
        'search_submit'           => 'Suchen',
        'category_label'          => 'Nach der Art der Sammlungen filtern, die ein Creator teilt',
        'category_any'            => 'Jede Sammlung',
        'category_option'         => '',
        'sort_label'              => '',
        'sort_active_this_season' => '',
        'sort_most_routes'        => '',
        'empty'                   => 'Es sind noch keine Creator aufgeführt.',
        'empty_for_category'      => 'Noch kein Creator teilt eine ":category"-Sammlung.',
        'empty_for_search'        => 'Keine Creator gefunden, die zu ":search" passen.',
    ],
    'featured' => [
        'title'         => 'Empfohlene Creator',
        'title_dungeon' => '',
        'see_all'       => 'Alle Creator anzeigen',
        /** Tooltip on a rail entry - it carries the name because the name itself may be clipped to an ellipsis */
        'entry_title'         => ':name - :routes',
        'route_count'         => '',
        'dungeon_route_count' => '',
    ],
    'stats' => [
        'route_count'              => '',
        'route_count_total'        => '',
        'season_route_count'       => '',
        'season_route_count_named' => '',
        'no_routes_this_season'    => '',
        'views'                    => '',
        'rating'                   => '',
        'last_published'           => '',
    ],
];
