<?php

return [
    'directory' => [
        'title'                   => 'Créateurs d\'itinéraires',
        'header'                  => 'Créateurs d\'itinéraires',
        'description'             => 'Parcourez les personnes qui créent des routes sur Keystone.guru. Les créateurs ayant des routes publiées sont listés automatiquement - ouvrez un profil pour voir leurs routes épinglées et où les retrouver ailleurs.',
        'search_label'            => 'Rechercher un créateur',
        'search_placeholder'      => 'Rechercher par nom',
        'search_submit'           => 'Rechercher',
        'category_label'          => 'Filtrer par le genre de collections qu\'un créateur partage',
        'category_any'            => 'Toutes les collections',
        'category_option'         => '',
        'sort_label'              => '',
        'sort_active_this_season' => '',
        'sort_most_routes'        => '',
        'empty'                   => 'Aucun créateur n\'est encore répertorié.',
        'empty_for_category'      => 'Aucun créateur ne partage encore de collection « :category ».',
        'empty_for_search'        => 'Aucun créateur ne correspond à « :search ».',
    ],
    'featured' => [
        'title'         => 'Créateurs en vedette',
        'title_dungeon' => '',
        'see_all'       => 'Voir tous les créateurs',
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
