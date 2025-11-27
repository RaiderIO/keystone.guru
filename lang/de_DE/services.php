<?php

return [
    'combatlogservice' => [
        'analyze_combat_log' => [
            'processing_error' => 'Kampflog konnte nicht verarbeitet werden: Fehler.',
            'verify_error'     => 'Kampflog konnte nicht überprüft werden: Fehler.',
        ],
    ],
    'mdt' => [
        'io' => [
            'export_string' => [
                'category' => [
                    'map_icon' => 'Kartensymbol',
                    'pull'     => 'Zug %d',
                    'title'    => 'Titel',
                ],
                'map_icon_contains_non_ascii_char_bug'         => 'Einer Ihrer Kommentare zu einem Kartensymbol enthält nicht-ASCII-Zeichen, die bekanntermaßen einen noch ungelösten Codierungsfehler in Keystone.guru auslösen. Ihr Kartenkommentar wurde von allen störenden Zeichen befreit, wir entschuldigen uns für die Unannehmlichkeiten und hoffen, dieses Problem bald zu lösen.',
                'map_icon_contains_non_ascii_char_bug_details' => 'Alter Kommentar: "%s", neuer Kommentar: "%s"',
                'route_title_contains_non_ascii_char_bug'      => 'Ihr Routentitel enthält nicht-ASCII-Zeichen, die bekanntermaßen einen noch ungelösten Codierungsfehler in Keystone.guru auslösen.
                                                        Ihr Routentitel wurde von allen störenden Zeichen befreit, wir entschuldigen uns für die Unannehmlichkeiten und hoffen, dieses Problem bald zu lösen.',
                'route_title_contains_non_ascii_char_bug_details'   => 'Alter Titel: %s, neuer Titel: %s',
                'unable_to_find_mdt_enemy_for_kg_caused_empty_pull' => 'Dieser Zug wurde entfernt, da alle ausgewählten Feinde in MDT nicht gefunden werden konnten, was zu einem ansonsten leeren Zug führte.',
                'unable_to_find_mdt_enemy_for_kg_enemy'             => 'MDT-Äquivalent für Keystone.guru-Feind mit NPC %s (enemy_id: %d, npc_id: %d) konnte nicht gefunden werden.',
                'unable_to_find_mdt_enemy_for_kg_enemy_details'     => 'Dies zeigt an, dass Ihre Route einen Feind tötet, dessen NPC MDT bekannt ist, aber Keystone.guru hat diesen Feind noch nicht mit einem MDT-Äquivalent gekoppelt (oder er existiert nicht in MDT).',
            ],
            'import_string' => [
                'category' => [
                    'awakened_obelisks' => 'Erweckte Obelisken',
                    'notes'             => 'Notizen',
                    'object'            => 'Objekt %d',
                    'pull'              => 'Zug %d',
                    'pulls'             => 'Züge',
                ],
                'limit_reached_brushlines'                             => 'Route konnte nicht importiert werden: mehr als das Maximum von :limit Linien.',
                'limit_reached_notes'                                  => 'Route konnte nicht importiert werden: mehr als das Maximum von :limit Notizen.',
                'limit_reached_paths'                                  => 'Route konnte nicht importiert werden: mehr als das Maximum von :limit Pfaden.',
                'limit_reached_pulls'                                  => 'Route konnte nicht importiert werden: mehr als das Maximum von :limit Zügen.',
                'object_out_of_bounds'                                 => 'Kommentar konnte nicht platziert werden: Kommentar ":comment" konnte nicht platziert werden, da das Objekt außerhalb der Grenzen liegt.',
                'unable_to_decode_mdt_import_string'                   => 'MDT-Import-String konnte nicht dekodiert werden',
                'unable_to_find_awakened_enemy_for_final_boss'         => 'Erweckter Feind %s (%s) beim Endboss in %s konnte nicht gefunden werden.',
                'unable_to_find_awakened_enemy_for_final_boss_details' => 'Dies weist darauf hin, dass Keystone.guru einen Zuordnungsfehler hat, der korrigiert werden muss. Sende die obige Warnung an mich und ich werde sie korrigieren.',
                'unable_to_find_awakened_obelisk_different_floor'      => 'Awakened Obelisk :name konnte nicht importiert werden, es befindet sich auf einem anderen Stockwerk als der Obelisk selbst. Keystone.guru unterstützt dies derzeit nicht.',
                'unable_to_find_awakened_obelisks'                     => 'Awakened Obelisks für deine Dungeon/Woche-Kombination konnten nicht gefunden werden. Deine Awakened Obelisk-Überspringungen werden nicht importiert.',
                'unable_to_find_enemies_pull_skipped'                  => 'Das Nichtfinden von Gegnern führte dazu, dass ein Pull übersprungen wurde.',
                'unable_to_find_enemies_pull_skipped_details'          => 'Dies kann darauf hinweisen, dass MDT kürzlich ein Update hatte, das in Keystone.guru noch nicht integriert ist.',
                'unable_to_find_floor_for_object'                      => 'Keystone.guru Stockwerk, das dem MDT Stockwerk-ID %d entspricht, konnte nicht gefunden werden.',
                'unable_to_find_floor_for_object_details'              => 'Dies weist darauf hin, dass MDT ein Stockwerk hat, das Keystone.guru nicht hat.',
                'unable_to_find_kg_equivalent_for_mdt_enemy'           => 'Keystone.guru-Äquivalent für MDT-Gegner %s mit NPC %s (ID: %s) konnte nicht gefunden werden.',
                'unable_to_find_kg_equivalent_for_mdt_enemy_details'   => 'Dies weist darauf hin, dass deine Route einen Gegner tötet, dessen NPC Keystone.guru bekannt ist, aber Keystone.guru diesen Gegner noch nicht zugeordnet hat.',
                'unable_to_find_mdt_enemy_for_clone_index'             => 'MDT-Gegner für Klon-Index %s und NPC-Index %s konnte nicht gefunden werden.',
                'unable_to_find_mdt_enemy_for_clone_index_details'     => 'Dies weist darauf hin, dass MDT einen Gegner zugeordnet hat, der in Keystone.guru noch nicht bekannt ist.',
                'unable_to_validate_mdt_import_string'                 => 'MDT-Importzeichenfolge konnte nicht validiert werden',
            ],
        ],
    ],
    'npcservice' => [
        'all_dungeons' => 'Alle Dungeons',
    ],
];
