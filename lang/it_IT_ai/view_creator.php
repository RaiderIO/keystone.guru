<?php

return [
    'directory' => [
        'title'                   => 'Creatori di percorsi',
        'header'                  => 'Creatori di percorsi',
        'description'             => 'Sfoglia le persone che creano percorsi su Keystone.guru. I creatori il cui numero di percorsi pubblicati è almeno :min sono elencati automaticamente - apri un profilo per vedere i loro percorsi appuntati e dove altro trovarli.',
        'search_label'            => 'Cerca un creatore',
        'search_placeholder'      => 'Cerca per nome',
        'search_submit'           => 'Cerca',
        'category_label'          => 'Crea percorsi per',
        'category_any'            => 'Tutti',
        'category_option'         => 'Giocatori di livello :category',
        'sort_label'              => 'Ordina per',
        'sort_active_this_season' => 'Attivi in questa stagione',
        'sort_most_routes'        => 'Più percorsi',
        'empty'                   => 'Non ci sono ancora creatori elencati.',
        'empty_for_category'      => 'Nessun creatore condivide ancora una raccolta ":category".',
        'empty_for_search'        => 'Nessun creatore trovato corrispondente a ":search".',
        'empty_for_dungeon'       => 'Nessun creatore pubblica ancora percorsi per :dungeon.',
        'filtered_to_dungeon'     => 'Creatori per :dungeon, prima quelli con i percorsi più popolari lì.',
        'clear_dungeon_filter'    => 'Tutti i dungeon',
    ],
    'featured' => [
        'title'           => 'Creatori in evidenza',
        'title_dungeon'   => 'Creatori per :dungeon',
        'see_all_dungeon' => 'Vedi tutti i creatori per :dungeon',
        /** Tooltip on a rail entry - it carries the name because a name past two lines is clipped to an ellipsis */
        'entry_title'         => ':name - :routes',
        'route_count'         => '{1} :count percorso|[2,*] :count percorsi',
        'dungeon_route_count' => '{1} :count percorso per :dungeon|[2,*] :count percorsi per :dungeon',
    ],
    'stats' => [
        'route_count'              => '{0} Nessun percorso|{1} :count percorso|[2,*] :count percorsi',
        'route_count_total'        => '{0} Nessun percorso pubblicato|{1} :count percorso in totale|[2,*] :count percorsi in totale',
        'season_route_count'       => '{1} :count percorso in questa stagione|[2,*] :count percorsi in questa stagione',
        'season_route_count_named' => '{0} Nessun percorso nella :season|{1} :count percorso nella :season|[2,*] :count percorsi nella :season',
        'no_routes_this_season'    => 'Nessun percorso in questa stagione',
        'views'                    => '{0} Nessuna visualizzazione|{1} :views visualizzazione|[2,*] :views visualizzazioni',
        'rating'                   => '{1} ★ :rating (:count valutazione)|[2,*] ★ :rating (:count valutazioni)',
        'last_published'           => 'Ultima pubblicazione: :time',
    ],
];
