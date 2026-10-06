<?php

return [
    'directory' => [
        'title'                   => 'Routen-Creator',
        'header'                  => 'Routen-Creator',
        'description'             => 'Entdecke die Leute, die auf Keystone.guru Routen erstellen. Creator mit veröffentlichten Routen (mindestens :min) werden automatisch aufgeführt - öffne ein Profil, um die angehefteten Routen zu sehen und wo du sie sonst noch findest.',
        'search_label'            => 'Nach einem Creator suchen',
        'search_placeholder'      => 'Nach Name suchen',
        'search_submit'           => 'Suchen',
        'category_label'          => 'Erstellt Routen für',
        'category_any'            => 'Alle',
        'category_option'         => 'Spieler (:category)',
        'sort_label'              => 'Sortieren nach',
        'sort_active_this_season' => 'Diese Saison aktiv',
        'sort_most_routes'        => 'Meiste Routen',
        'empty'                   => 'Es sind noch keine Creator aufgeführt.',
        'empty_for_category'      => 'Noch kein Creator teilt eine ":category"-Sammlung.',
        'empty_for_search'        => 'Keine Creator gefunden, die zu ":search" passen.',
        'empty_for_dungeon'       => 'Noch veröffentlicht kein Creator Routen für :dungeon.',
        'filtered_to_dungeon'     => 'Creator für :dungeon – zuerst diejenigen mit den beliebtesten Routen für diesen Dungeon.',
        'clear_dungeon_filter'    => 'Alle Dungeons',
    ],
    'featured' => [
        'title'           => 'Empfohlene Creator',
        'title_dungeon'   => 'Creator für :dungeon',
        'see_all_dungeon' => 'Alle Creator für :dungeon ansehen',
        /** Tooltip on a rail entry - it carries the name because a name past two lines is clipped to an ellipsis */
        'entry_title'         => ':name - :routes',
        'route_count'         => '{1} :count Route|[2,*] :count Routen',
        'dungeon_route_count' => '{1} :count Route für :dungeon|[2,*] :count Routen für :dungeon',
    ],
    'stats' => [
        'route_count'              => '{0} Keine Routen|{1} :count Route|[2,*] :count Routen',
        'route_count_total'        => '{0} Keine veröffentlichten Routen|{1} :count Route insgesamt|[2,*] :count Routen insgesamt',
        'season_route_count'       => '{1} :count Route in dieser Saison|[2,*] :count Routen in dieser Saison',
        'season_route_count_named' => '{0} Keine Routen in :season|{1} :count Route in :season|[2,*] :count Routen in :season',
        'no_routes_this_season'    => 'Keine Routen in dieser Saison',
        'views'                    => '{0} Keine Aufrufe|{1} :views Aufruf|[2,*] :views Aufrufe',
        'rating'                   => '{1} ★ :rating (:count Bewertung)|[2,*] ★ :rating (:count Bewertungen)',
        'last_published'           => 'Zuletzt veröffentlicht :time',
    ],
];
