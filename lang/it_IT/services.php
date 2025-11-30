<?php

return [
    'combatlogservice' => [
        'analyze_combat_log' => [
            'processing_error' => 'Impossibile elaborare il registro di combattimento: errore.',
            'verify_error'     => 'Impossibile verificare il registro di combattimento: errore.',
        ],
    ],
    'mdt' => [
        'io' => [
            'export_string' => [
                'category' => [
                    'map_icon' => 'Icona mappa',
                    'pull'     => 'Pull %d',
                    'title'    => 'Titolo',
                ],
                'map_icon_contains_non_ascii_char_bug'              => 'Uno dei tuoi commenti su un\'icona della mappa contiene caratteri non ASCII che sono noti per innescare un bug di codifica ancora irrisolto in Keystone.guru. Il tuo commento sulla mappa è stato privato di tutti i caratteri che causano problemi, ci scusiamo per l\'inconveniente e speriamo di risolvere presto questo problema.',
                'map_icon_contains_non_ascii_char_bug_details'      => 'Vecchio commento: "%s", nuovo commento: "%s"',
                'route_title_contains_non_ascii_char_bug'           => 'Il titolo del tuo percorso contiene caratteri non ASCII che sono noti per innescare un bug di codifica ancora irrisolto in Keystone.guru. Il titolo del tuo percorso è stato privato di tutti i caratteri che causano problemi, ci scusiamo per l\'inconveniente e speriamo di risolvere presto questo problema.',
                'route_title_contains_non_ascii_char_bug_details'   => 'Vecchio titolo: %s, nuovo titolo: %s',
                'unable_to_find_mdt_enemy_for_kg_caused_empty_pull' => 'Questo pull è stato rimosso poiché tutti i nemici selezionati non sono stati trovati in MDT, risultando in un pull altrimenti vuoto.',
                'unable_to_find_mdt_enemy_for_kg_enemy'             => 'Impossibile trovare un equivalente MDT per il nemico di Keystone.guru con NPC %s (enemy_id: %d, npc_id: %d).',
                'unable_to_find_mdt_enemy_for_kg_enemy_details'     => 'Ciò indica che il tuo percorso uccide un nemico il cui NPC è noto a MDT, ma Keystone.guru non ha ancora associato quel nemico a un equivalente MDT (o non esiste in MDT).',
            ],
            'import_string' => [
                'category' => [
                    'awakened_obelisks' => 'Obelischi Risvegliati',
                    'notes'             => 'Note',
                    'object'            => 'Oggetto %d',
                    'pull'              => 'Pull %d',
                    'pulls'             => 'Pulls',
                ],
                'limit_reached_brushlines'                             => 'Impossibile importare il percorso: più del massimo di :limit linee.',
                'limit_reached_notes'                                  => 'Impossibile importare il percorso: più del massimo di :limit note.',
                'limit_reached_paths'                                  => 'Impossibile importare il percorso: più del massimo di :limit percorsi.',
                'limit_reached_pulls'                                  => 'Impossibile importare il percorso: più del massimo di :limit pulls.',
                'object_out_of_bounds'                                 => 'Impossibile posizionare il commento: impossibile posizionare il commento ":comment" l\'oggetto è fuori dai limiti.',
                'unable_to_decode_mdt_import_string'                   => 'Impossibile decodificare la stringa di importazione MDT',
                'unable_to_find_awakened_enemy_for_final_boss'         => 'Impossibile trovare Nemico Risvegliato %s (%s) al boss finale in %s.',
                'unable_to_find_awakened_enemy_for_final_boss_details' => 'Questo indica che Keystone.guru ha un errore di mappatura che dovrà essere corretto. Invia l\'avviso sopra a me e lo correggerò.',
                'unable_to_find_awakened_obelisk_different_floor'      => 'Impossibile importare l\'Obelisco Risvegliato :name, si trova su un piano diverso dall\'Obelisco stesso. Keystone.guru non supporta questo al momento.',
                'unable_to_find_awakened_obelisks'                     => 'Impossibile trovare gli Obelischi Risvegliati per la tua combinazione di dungeon/settimana. I tuoi salti dell\'Obelisco Risvegliato non saranno importati.',
                'unable_to_find_enemies_pull_skipped'                  => 'Il fallimento nel trovare nemici ha comportato il salto di un pull.',
                'unable_to_find_enemies_pull_skipped_details'          => 'Questo può indicare che MDT ha recentemente avuto un aggiornamento non ancora integrato in Keystone.guru.',
                'unable_to_find_floor_for_object'                      => 'Impossibile trovare il piano di Keystone.guru che corrisponde all\'ID del piano MDT %d.',
                'unable_to_find_floor_for_object_details'              => 'Questo indica che MDT ha un piano che Keystone.guru non ha.',
                'unable_to_find_kg_equivalent_for_mdt_enemy'           => 'Impossibile trovare l\'equivalente di Keystone.guru per il nemico MDT %s con NPC %s (id: %s).',
                'unable_to_find_kg_equivalent_for_mdt_enemy_details'   => 'Questo indica che il tuo percorso uccide un nemico di cui l\'NPC è noto a Keystone.guru, ma Keystone.guru non ha ancora mappato quel nemico.',
                'unable_to_find_mdt_enemy_for_clone_index'             => 'Impossibile trovare il nemico MDT per l\'indice di clonazione %s e l\'indice NPC %s.',
                'unable_to_find_mdt_enemy_for_clone_index_details'     => 'Questo indica che MDT ha mappato un nemico che non è ancora conosciuto in Keystone.guru.',
                'unable_to_validate_mdt_import_string'                 => 'Impossibile convalidare la stringa di importazione MDT',
            ],
        ],
    ],
    'npcservice' => [
        'all_dungeons' => 'Tutti i dungeon',
    ],
];
