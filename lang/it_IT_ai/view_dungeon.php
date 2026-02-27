<?php

return [
    'dungeonroute' => [
        'search' => [
            'gameversion' => [
                'dungeon' => [
                    'title'                                  => '',
                    'description'                            => '',
                    'linkpreview_default_description_search' => '',
                    'linkpreview_title'                      => '',
                ],
            ],
            'list' => [
                'title'       => '',
                'header'      => '',
                'description' => '',
            ],
        ],
    ],
    'explore' => [
        'gameversion' => [
            'list' => [
                'title'             => 'Esplora',
                'header'            => 'Esplora dungeon',
                'description'       => 'Esplorare un dungeon ti permette di vedere la disposizione del dungeon e i nemici presenti. Ideale per visualizzare semplicemente il dungeon senza creare un percorso.',
                'heatmap_available' => 'Mappa termica disponibile per il dungeon',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => 'Qualsiasi',
                'select_floor'            => 'Seleziona piano',
                'view_heatmap_fullscreen' => 'Visualizza a schermo intero',
            ],
            'view' => [
                'title' => '',
            ],
        ],
    ],
    'heatmap' => [
        'gameversion' => [
            'list' => [
                'title'             => 'Mappe termiche',
                'header'            => 'Mappe termiche del dungeon',
                'raider_io'         => 'Raider.IO',
                'description'       => 'Supportato da :raiderIO, le mappe termiche possono mostrarti informazioni inestimabili su quali nemici vengono uccisi dai giocatori, dove si fanno uccidere o lanciano certi incantesimi. Filtri per livello chiave, livello oggetto, composizione della squadra e molti altri ti permettono di concentrarti sui dati rilevanti per le tue esigenze.',
                'heatmap_available' => 'Mappa termica disponibile per il dungeon',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => 'Qualsiasi',
                'select_floor'            => 'Seleziona piano',
                'view_heatmap_fullscreen' => 'Visualizza a schermo intero',
            ],
        ],
    ],

];
