<?php

return [
    'directory' => [
        'title'                   => 'Créateurs d\'itinéraires',
        'header'                  => 'Créateurs d\'itinéraires',
        'description'             => 'Parcourez les personnes qui créent des routes sur Keystone.guru. Les créateurs dont le nombre de routes publiées atteint au moins :min sont listés automatiquement - ouvrez un profil pour voir leurs routes épinglées et où les retrouver ailleurs.',
        'search_label'            => 'Rechercher un créateur',
        'search_placeholder'      => 'Rechercher par nom',
        'search_submit'           => 'Rechercher',
        'category_label'          => 'Crée des itinéraires pour',
        'category_any'            => 'Tout le monde',
        'category_option'         => 'Joueurs de niveau :category',
        'sort_label'              => 'Trier par',
        'sort_active_this_season' => 'Actifs cette saison',
        'sort_most_routes'        => 'Le plus d\'itinéraires',
        'empty'                   => 'Aucun créateur n\'est encore répertorié.',
        'empty_for_category'      => 'Aucun créateur ne partage encore de collection « :category ».',
        'empty_for_search'        => 'Aucun créateur ne correspond à « :search ».',
        'empty_for_dungeon'       => 'Aucun créateur ne publie encore d\'itinéraires pour :dungeon.',
        'filtered_to_dungeon'     => 'Créateurs pour :dungeon, ceux dont les itinéraires y sont les plus populaires en premier.',
        'clear_dungeon_filter'    => 'Tous les donjons',
    ],
    'featured' => [
        'title'           => 'Créateurs en vedette',
        'title_dungeon'   => 'Créateurs pour :dungeon',
        'see_all_dungeon' => 'Voir tous les créateurs pour :dungeon',
        /** Tooltip on a rail entry - it carries the name because a name past two lines is clipped to an ellipsis */
        'entry_title'         => ':name - :routes',
        'route_count'         => '{1} :count itinéraire|[2,*] :count itinéraires',
        'dungeon_route_count' => '{1} :count itinéraire (:dungeon)|[2,*] :count itinéraires (:dungeon)',
    ],
    'stats' => [
        'route_count'              => '{0} Aucun itinéraire|{1} :count itinéraire|[2,*] :count itinéraires',
        'route_count_total'        => '{0} Aucun itinéraire publié|{1} :count itinéraire au total|[2,*] :count itinéraires au total',
        'season_route_count'       => '{1} :count itinéraire cette saison|[2,*] :count itinéraires cette saison',
        'season_route_count_named' => '{0} Aucun itinéraire en :season|{1} :count itinéraire en :season|[2,*] :count itinéraires en :season',
        'no_routes_this_season'    => 'Aucun itinéraire cette saison',
        'views'                    => '{0} Aucune vue|{1} :views vue|[2,*] :views vues',
        'rating'                   => '{1} ★ :rating (:count note)|[2,*] ★ :rating (:count notes)',
        'last_published'           => 'Dernière publication : :time',
    ],
];
