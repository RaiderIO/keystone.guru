<?php

return [
    'directory' => [
        'title'              => 'Creatori di percorsi',
        'header'             => 'Creatori di percorsi',
        'description'        => 'Sfoglia le persone che creano percorsi su Keystone.guru. I creatori con percorsi pubblicati sono elencati automaticamente - apri un profilo per vedere i loro percorsi appuntati e dove altro trovarli.',
        'search_label'       => 'Cerca un creatore',
        'search_placeholder' => 'Cerca per nome',
        'search_submit'      => 'Cerca',
        'empty'              => 'Non ci sono ancora creatori elencati.',
        'empty_for_search'   => 'Nessun creatore trovato corrispondente a ":search".',
    ],
    'featured' => [
        'title'   => 'Creatori in evidenza',
        'see_all' => 'Vedi tutti i creatori',
        /** Tooltip on a rail entry - it carries the name because the name itself may be clipped to an ellipsis */
        'entry_title' => ':name - :routes',
    ],
    'card' => [
        'route_count' => '{0} Nessun percorso pubblicato|{1} :count percorso pubblicato|[2,*] :count percorsi pubblicati',
    ],
];
