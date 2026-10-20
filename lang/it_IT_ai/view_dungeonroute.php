<?php

return [

    'discover' => [
        'dungeon' => [
            'overview' => [
                'weekly_routes'    => 'Percorsi settimanali Raider.IO',
                'community_routes' => 'Percorsi della community',
                'archetypes'       => [
                    'pug_friendly' => [
                        'label'       => 'Amichevole per Pug',
                        'description' => 'Pull tolleranti per gruppi di sconosciuti',
                    ],
                    'expert' => [
                        'label'       => 'Esperto',
                        'description' => 'Ottimizzato per gruppi coordinati',
                    ],
                    'title' => [
                        'label'       => 'Titolo',
                        'description' => 'Il percorso usato dallo 0,5% migliore per aumentare il rating',
                    ],
                ],
            ],
        ],
        'discover' => [
            'title'                  => 'Percorsi',
            'popular'                => 'Percorsi popolari',
            'newly_published_routes' => 'Percorsi appena pubblicati',
        ],
        'panel' => [
            'show_more' => 'Mostra di più',
        ],
    ],
    'livesession' => [
        'title' => 'Sessione live - :title',
        'view'  => [
            'any' => 'Qualsiasi',
        ],
    ],
    'edit' => [
        'title'                                   => 'Modifica %s',
        'linkpreview_title'                       => '%s | Keystone.guru',
        'linkpreview_default_description'         => 'Modifica percorso M+ per il dungeon %s di %s',
        'linkpreview_default_description_sandbox' => 'Modifica percorso M+ per il dungeon %s',
    ],
    'embed' => [
        'title'            => 'Incorpora :routeTitle',
        'any'              => 'Qualsiasi',
        'select_floor'     => 'Seleziona piano',
        'affixes_title'    => 'Affissi',
        'affixes_selected' => '{0} affissi selezionati',
        'view_route'       => 'Visualizza percorso',
        'present_route'    => 'Presenta percorso',
        'copy_mdt_string'  => 'Copia stringa MDT',
    ],
    'limitreached' => [
        'title'                     => 'Limite raggiunto',
        'header'                    => 'Limite raggiunto',
        'limit_reached_description' => 'Hai raggiunto il numero massimo di percorsi che puoi creare (%s). Considera di diventare un Patron per continuare a creare più percorsi o elimina alcuni dei tuoi percorsi esistenti. Grazie per aver utilizzato il sito!',
        'become_a_patreon'          => 'Diventa un Patron di %s!',
    ],
    'new' => [
        'title' => 'Nuovo percorso',
    ],
    'newtemporary' => [
        'title'  => 'Crea percorso temporaneo',
        'header' => 'Nuovo percorso temporaneo',
    ],
    'view' => [
        'any'                                     => 'Qualsiasi',
        'linkpreview_title'                       => '%s',
        'linkpreview_default_description'         => 'Percorso M+ per il dungeon %s di %s.',
        'linkpreview_default_description_sandbox' => 'Percorso M+ temporaneo per il dungeon %s.',
        'linkpreview_default_description_explore' => 'Esplora %s.',
        'linkpreview_default_description_heatmap' => 'Guarda una mappa di calore di %s per vedere quali scelte sta facendo la community.',
    ],

];
