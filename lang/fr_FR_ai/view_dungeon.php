<?php

return [

    'dungeonroute' => [
        'search' => [
            'gameversion' => [
                'dungeon' => [
                    'title'                                  => 'Recherchez des itinéraires pour :dungeon',
                    'description'                            => 'Recherchez des itinéraires par titre, niveau de clé, affixes, forces ennemies, note et plus. Sélectionnez un donjon pour commencer.',
                    'linkpreview_default_description_search' => 'Trouvez des itinéraires M+ pour :dungeon',
                    'linkpreview_title'                      => ':title | Keystone.guru',
                ],
            ],
            'list' => [
                'title'       => 'Recherchez des itinéraires',
                'header'      => 'Recherchez des itinéraires',
                'description' => 'Recherchez des itinéraires par titre, niveau de clé, affixes, forces ennemies, note et plus. Sélectionnez un donjon pour commencer.',
            ],
        ],
    ],
    'explore' => [
        'gameversion' => [
            'list' => [
                'title'             => 'Explorer',
                'header'            => 'Explorer le donjon',
                'description'       => 'Explorer un donjon vous permet de voir la disposition du donjon et les ennemis présents. Idéal pour simplement visualiser le donjon sans créer d\'itinéraire.',
                'heatmap_available' => 'Carte de chaleur disponible pour le donjon',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => 'N\'importe lequel',
                'select_floor'            => 'Sélectionner l\'étage',
                'view_heatmap_fullscreen' => 'Voir en plein écran',
            ],
            'view' => [
                'title' => 'Explorez :dungeon',
            ],
        ],
    ],
    'heatmap' => [
        'gameversion' => [
            'list' => [
                'title'             => 'Cartes de chaleur',
                'header'            => 'Cartes de chaleur du donjon',
                'raider_io'         => 'Raider.IO',
                'description'       => 'Propulsé par :raiderIO, les cartes de chaleur peuvent vous montrer des informations inestimables sur quels ennemis sont tués par les joueurs, où ils se font tuer ou lancent certains sorts. Des filtres pour le niveau de clé, le niveau d\'objet, la composition de l\'équipe et bien d\'autres vous permettent de vous concentrer sur les données pertinentes pour vos besoins.',
                'heatmap_available' => 'Carte de chaleur disponible pour le donjon',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => 'N\'importe lequel',
                'select_floor'            => 'Sélectionner l\'étage',
                'view_heatmap_fullscreen' => 'Voir en plein écran',
            ],
        ],
    ],

];
