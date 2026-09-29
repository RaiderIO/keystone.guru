<?php

return [

    'discover' => [
        'dungeon' => [
            'overview' => [
                'weekly_routes'    => 'Wöchentliche Raider.IO-Routen',
                'community_routes' => 'Community-Routen',
                'archetypes'       => [
                    'pug_friendly' => [
                        'label'       => 'PUG-freundlich',
                        'description' => 'Nachsichtige Pulls für Randomgruppen',
                    ],
                    'expert' => [
                        'label'       => 'Experte',
                        'description' => 'Optimiert für eingespielte Gruppen',
                    ],
                    'title' => [
                        'label'       => 'Titel',
                        'description' => 'Die Route, mit der die besten 0,5% ihre Wertung steigern',
                    ],
                ],
            ],
        ],
        'discover' => [
            'title'                  => 'Routen',
            'popular'                => 'Beliebte Routen',
            'newly_published_routes' => 'Neu veröffentlichte Routen',
        ],
        'panel' => [
            'show_more' => 'Mehr anzeigen',
        ],
    ],
    'livesession' => [
        'title' => 'Live-Sitzung - :title',
        'view'  => [
            'any' => 'Jeder',
        ],
    ],
    'edit' => [
        'title'                                   => '%s bearbeiten',
        'linkpreview_title'                       => '%s | Keystone.guru',
        'linkpreview_default_description'         => 'M+-Route für Dungeon %s von %s bearbeiten',
        'linkpreview_default_description_sandbox' => 'M+-Route für Dungeon %s bearbeiten',
    ],
    'embed' => [
        'title'            => 'Einbetten :routeTitle',
        'any'              => 'Jeder',
        'select_floor'     => 'Etage auswählen',
        'affixes_title'    => 'Affixe',
        'affixes_selected' => '{0} Affixe ausgewählt',
        'view_route'       => 'Route ansehen',
        'present_route'    => 'Route präsentieren',
        'copy_mdt_string'  => 'MDT-String kopieren',
    ],
    'limitreached' => [
        'title'                     => 'Limit erreicht',
        'header'                    => 'Limit erreicht',
        'limit_reached_description' => 'Du hast die maximale Anzahl von Routen erreicht, die du erstellen darfst (%s). Bitte überlege, Patron zu werden, um weiterhin mehr Routen zu erstellen, oder lösche einige deiner bestehenden Routen. Vielen Dank für die Nutzung der Seite!',
        'become_a_patreon'          => 'Werde ein %s Patron!',
    ],
    'new' => [
        'title' => 'Neue Route',
    ],
    'newtemporary' => [
        'title'  => 'Temporäre Route erstellen',
        'header' => 'Neue temporäre Route',
    ],
    'sandboxclaimed' => [
        'title'               => 'Route bereits beansprucht',
        'header'              => 'Route bereits beansprucht',
        'claimed_description' => 'Diese Route wurde bereits von jemandem beansprucht (oder du hast die Zurück-Taste in deinem Browser verwendet, um hierher zu navigieren).',
    ],
    'unavailable' => [
        'title'                   => 'Unveröffentlichte Route',
        'unavailable_description' => 'Du bist nicht berechtigt, diese Route anzusehen. Bitte den Autor der Route, die Freigabeeinstellungen der Route zu ändern, damit du sie ansehen kannst.',
    ],
    'view' => [
        'any'                                     => 'Jeder',
        'linkpreview_title'                       => '%s',
        'linkpreview_default_description'         => 'M+-Route für Dungeon %s von %s.',
        'linkpreview_default_description_sandbox' => 'Temporäre M+-Route für Dungeon %s.',
        'linkpreview_default_description_explore' => '%s erkunden.',
        'linkpreview_default_description_heatmap' => 'Sieh dir eine Heatmap von %s an, um zu sehen, welche Entscheidungen die Community trifft.',
    ],

];
