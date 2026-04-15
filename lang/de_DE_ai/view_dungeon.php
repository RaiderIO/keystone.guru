<?php

return [
    'dungeonroute' => [
        'search' => [
            'gameversion' => [
                'dungeon' => [
                    'title'                                  => 'Suche Routen für :dungeon',
                    'description'                            => 'Suche nach Routen nach Titel, Schlüsselstufe, Affixen, Feindkräften, Bewertung und mehr. Wähle einen Dungeon, um zu beginnen.',
                    'linkpreview_default_description_search' => 'Finde M+ Routen für :dungeon',
                    'linkpreview_title'                      => ':title | Keystone.guru',
                ],
            ],
            'list' => [
                'title'       => 'Routen suchen',
                'header'      => 'Routen suchen',
                'description' => 'Suche nach Routen nach Titel, Schlüsselstufe, Affixen, Feindkräften, Bewertung und mehr. Wähle einen Dungeon, um zu beginnen.',
            ],
        ],
    ],
    'explore' => [
        'gameversion' => [
            'list' => [
                'title'             => 'Erkunden',
                'header'            => 'Dungeon erkunden',
                'description'       => 'Das Erkunden eines Dungeons ermöglicht es Ihnen, das Layout des Dungeons und die vorhandenen Feinde zu sehen. Ideal, um den Dungeon einfach anzusehen, ohne eine Route zu erstellen.',
                'heatmap_available' => 'Heatmap für Dungeon verfügbar',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => 'Beliebig',
                'select_floor'            => 'Etage auswählen',
                'view_heatmap_fullscreen' => 'Vollbild anzeigen',
            ],
            'view' => [
                'title' => 'Erkunde :dungeon',
            ],
        ],
    ],
    'heatmap' => [
        'gameversion' => [
            'list' => [
                'title'             => 'Heatmaps',
                'header'            => 'Dungeon-Heatmaps',
                'raider_io'         => 'Raider.IO',
                'description'       => 'Angetrieben von :raiderIO, können Heatmaps Ihnen wertvolle Informationen darüber geben, welche Feinde von Spielern getötet werden, wo sie sterben oder bestimmte Zauber wirken. Filter für Schlüsselstufe, Gegenstandsstufe, Teamzusammensetzung und viele mehr ermöglichen es Ihnen, sich auf die für Ihre Bedürfnisse relevanten Daten zu konzentrieren.',
                'heatmap_available' => 'Heatmap für Dungeon verfügbar',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => 'Beliebig',
                'select_floor'            => 'Etage auswählen',
                'view_heatmap_fullscreen' => 'Vollbild anzeigen',
            ],
        ],
    ],

];
