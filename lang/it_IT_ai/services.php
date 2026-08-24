<?php

return [

    'mdt' => [
        'io' => [
            'export_string' => [
                'category' => [
                    'pull'         => 'Pull %d',
                    'title'        => 'Titolo',
                    'map_icon'     => 'Icona mappa',
                    'raid_markers' => 'Marcatori di incursione',
                ],
                'unable_to_find_mdt_enemy_for_kg_enemy'             => 'Impossibile trovare un equivalente MDT per il nemico di Keystone.guru con NPC %s (enemy_id: %d, npc_id: %d).',
                'unable_to_find_mdt_enemy_for_kg_enemy_details'     => 'Ciò indica che il tuo percorso uccide un nemico il cui NPC è noto a MDT, ma Keystone.guru non ha ancora associato quel nemico a un equivalente MDT (o non esiste in MDT).',
                'unable_to_find_mdt_enemy_for_kg_caused_empty_pull' => 'Questo pull è stato rimosso poiché tutti i nemici selezionati non sono stati trovati in MDT, risultando in un pull altrimenti vuoto.',
                'unable_to_find_mdt_enemy_for_kg_raid_marker'       => 'Impossibile trovare l\'equivalente MDT per il nemico con un marcatore di incursione %s (npc_id: %s).',
                'route_title_contains_non_ascii_char_bug'           => 'Il titolo del tuo percorso contiene caratteri non ASCII che sono noti per innescare un bug di codifica ancora irrisolto in Keystone.guru. Il titolo del tuo percorso è stato privato di tutti i caratteri che causano problemi, ci scusiamo per l\'inconveniente e speriamo di risolvere presto questo problema.',
                'route_title_contains_non_ascii_char_bug_details'   => 'Vecchio titolo: %s, nuovo titolo: %s',
                'map_icon_contains_non_ascii_char_bug'              => 'Uno dei tuoi commenti su un\'icona della mappa contiene caratteri non ASCII che sono noti per innescare un bug di codifica ancora irrisolto in Keystone.guru. Il tuo commento sulla mappa è stato privato di tutti i caratteri che causano problemi, ci scusiamo per l\'inconveniente e speriamo di risolvere presto questo problema.',
                'map_icon_contains_non_ascii_char_bug_details'      => 'Vecchio commento: "%s", nuovo commento: "%s"',
            ],
            'import_string' => [
                'category' => [
                    'awakened_obelisks' => 'Obelischi Risvegliati',
                    'pulls'             => 'Pulls',
                    'notes'             => 'Note',
                    'arrows'            => 'Frecce',
                    'pull'              => 'Pull %d',
                    'object'            => 'Oggetto %d',
                    'raid_markers'      => 'Marcatori di incursione',
                ],
                'object_out_of_bounds'                                 => 'Impossibile posizionare il commento: impossibile posizionare il commento ":comment" l\'oggetto è fuori dai limiti.',
                'limit_reached_pulls'                                  => 'Impossibile importare il percorso: più del massimo di :limit pulls.',
                'limit_reached_brushlines'                             => 'Impossibile importare il percorso: più del massimo di :limit linee.',
                'limit_reached_paths'                                  => 'Impossibile importare il percorso: più del massimo di :limit percorsi.',
                'limit_reached_arrows'                                 => 'Impossibile importare il percorso: più del massimo di :limit frecce.',
                'limit_reached_notes'                                  => 'Impossibile importare il percorso: più del massimo di :limit note.',
                'unable_to_find_floor_for_object'                      => 'Impossibile trovare il piano di Keystone.guru che corrisponde all\'ID del piano MDT %d.',
                'unable_to_find_floor_for_object_details'              => 'Questo indica che MDT ha un piano che Keystone.guru non ha.',
                'unable_to_find_mdt_enemy_for_clone_index'             => 'Impossibile trovare il nemico MDT per l\'indice di clonazione %s e l\'indice NPC %s.',
                'unable_to_find_mdt_enemy_for_clone_index_details'     => 'Questo indica che MDT ha mappato un nemico che non è ancora conosciuto in Keystone.guru.',
                'unable_to_find_kg_equivalent_for_mdt_enemy'           => 'Impossibile trovare l\'equivalente di Keystone.guru per il nemico MDT %s con NPC %s (id: %s).',
                'unable_to_find_kg_equivalent_for_mdt_enemy_details'   => 'Questo indica che il tuo percorso uccide un nemico di cui l\'NPC è noto a Keystone.guru, ma Keystone.guru non ha ancora mappato quel nemico.',
                'unable_to_find_awakened_enemy_for_final_boss'         => 'Impossibile trovare Nemico Risvegliato %s (%s) al boss finale in %s.',
                'unable_to_find_awakened_enemy_for_final_boss_details' => 'Questo indica che Keystone.guru ha un errore di mappatura che dovrà essere corretto. Invia l\'avviso sopra a me e lo correggerò.',
                'unable_to_find_enemies_pull_skipped'                  => 'Il fallimento nel trovare nemici ha comportato il salto di un pull.',
                'unable_to_find_enemies_pull_skipped_details'          => 'Questo può indicare che MDT ha recentemente avuto un aggiornamento non ancora integrato in Keystone.guru.',
                'unable_to_find_awakened_obelisks'                     => 'Impossibile trovare gli Obelischi Risvegliati per la tua combinazione di dungeon/settimana. I tuoi salti dell\'Obelisco Risvegliato non saranno importati.',
                'unable_to_find_awakened_obelisk_different_floor'      => 'Impossibile importare l\'Obelisco Risvegliato :name, si trova su un piano diverso dall\'Obelisco stesso. Keystone.guru non supporta questo al momento.',
                'unable_to_find_awakened_obelisk_enemy'                => 'Impossibile importare l\'Obelisco Risvegliato :name, il suo nemico non è stato possibile risolverlo per la tua combinazione di dungeon/settimana.',
                'unable_to_decode_mdt_import_string'                   => 'Impossibile decodificare la stringa di importazione MDT',
                'unable_to_validate_mdt_import_string'                 => 'Impossibile convalidare la stringa di importazione MDT',
            ],
        ],
    ],
    'npcservice' => [
        'all_dungeons' => 'Tutti i dungeon',
    ],
    'combatlogservice' => [
        'analyze_combat_log' => [
            'verify_error'     => 'Impossibile verificare il registro di combattimento: errore.',
            'processing_error' => 'Impossibile elaborare il registro di combattimento: errore.',
        ],
    ],
    'combatlog' => [
        'enemy_failure_analysis' => [
            'verdict' => [
                'npc_not_mapped'       => 'NPC non mappato',
                'no_enemy_in_range'    => 'Nessun nemico a portata',
                'enemies_exhausted'    => 'Più nel gioco che nella mappatura',
                'wrong_floor_artifact' => 'Probabile anomalia dovuta a un piano errato',
            ],
            'suggestion' => [
                'npc_not_mapped'       => ':npc non è affatto presente in questa versione di mappatura, eppure qui è stato ingaggiato :count volte su :routes percorsi. Aggiungilo (o il suo pacchetto) in questa posizione.',
                'no_enemy_in_range'    => 'Il :npc mappato più vicino (nemico :enemy_id) si trova a :distance iarde, oltre la portata di ingaggio di :range iarde. Probabilmente qui manca un pacchetto di :npc, oppure è mappato nel posto sbagliato.',
                'enemies_exhausted'    => 'Nel raggio d\'azione risultano :enemies :npc mappati, ma i percorsi qui falliscono comunque circa :avg volte ciascuno: nel gioco questo pacchetto contiene probabilmente più :npc di quanti ne preveda la mappatura.',
                'wrong_floor_artifact' => 'Nessun :npc a portata su questo piano, ma ce n\'è uno entro :distance iarde su un altro piano. Il piano registrato viene dedotto dall\'NPC precedente nel log, quindi l\'anomalia è molto probabilmente dovuta a tale deduzione: verifica prima di modificare la mappatura.',
            ],
        ],
    ],

];
