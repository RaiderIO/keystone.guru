<?php

return [
    'combatlogservice' => [
        'analyze_combat_log' => [
            'processing_error' => 'Impossible de traiter le journal de combat : erreur.',
            'verify_error'     => 'Impossible de vérifier le journal de combat : erreur.',
        ],
    ],
    'mdt' => [
        'io' => [
            'export_string' => [
                'category' => [
                    'map_icon' => 'Icône de carte',
                    'pull'     => 'Tirage %d',
                    'title'    => 'Titre',
                ],
                'map_icon_contains_non_ascii_char_bug'         => 'Un de vos commentaires sur une icône de carte contient des caractères non-ASCII connus pour déclencher un bug d\'encodage encore non résolu dans Keystone.guru. Votre commentaire de carte a été dépouillé de tous les caractères offensants, nous nous excusons pour le désagrément et espérons résoudre ce problème bientôt.',
                'map_icon_contains_non_ascii_char_bug_details' => 'Ancien commentaire : "%s", nouveau commentaire : "%s"',
                'route_title_contains_non_ascii_char_bug'      => 'Le titre de votre route contient des caractères non-ASCII connus pour déclencher un bug d\'encodage encore non résolu dans Keystone.guru.
                                                        Le titre de votre route a été dépouillé de tous les caractères offensants, nous nous excusons pour le désagrément et espérons résoudre ce problème bientôt.',
                'route_title_contains_non_ascii_char_bug_details'   => 'Ancien titre : %s, nouveau titre : %s',
                'unable_to_find_mdt_enemy_for_kg_caused_empty_pull' => 'Ce tirage a été supprimé car tous les ennemis sélectionnés n\'ont pas pu être trouvés dans MDT, entraînant un tirage vide.',
                'unable_to_find_mdt_enemy_for_kg_enemy'             => 'Impossible de trouver l\'équivalent MDT pour l\'ennemi Keystone.guru avec le PNJ %s (enemy_id: %d, npc_id: %d).',
                'unable_to_find_mdt_enemy_for_kg_enemy_details'     => 'Cela indique que votre route tue un ennemi dont le PNJ est connu de MDT, mais Keystone.guru n\'a pas encore couplé cet ennemi à un équivalent MDT (ou il n\'existe pas dans MDT).',
            ],
            'import_string' => [
                'category' => [
                    'awakened_obelisks' => 'Obélisques éveillés',
                    'notes'             => 'Notes',
                    'object'            => 'Objet %d',
                    'pull'              => 'Tirage %d',
                    'pulls'             => 'Tirages',
                ],
                'limit_reached_brushlines'                             => 'Impossible d\'importer la route : plus que le maximum de :limit lignes.',
                'limit_reached_notes'                                  => 'Impossible d\'importer la route : plus que le maximum de :limit notes.',
                'limit_reached_paths'                                  => 'Impossible d\'importer la route : plus que le maximum de :limit chemins.',
                'limit_reached_pulls'                                  => 'Impossible d\'importer la route : plus que le maximum de :limit tirages.',
                'object_out_of_bounds'                                 => 'Impossible de placer le commentaire : impossible de placer le commentaire ":comment" l\'objet est hors limites.',
                'unable_to_decode_mdt_import_string'                   => 'Impossible de décoder la chaîne d\'importation MDT',
                'unable_to_find_awakened_enemy_for_final_boss'         => 'Impossible de trouver l\'ennemi éveillé %s (%s) chez le boss final dans %s.',
                'unable_to_find_awakened_enemy_for_final_boss_details' => 'Cela indique que Keystone.guru a une erreur de cartographie qui devra être corrigée. Envoyez-moi l\'avertissement ci-dessus et je le corrigerai.',
                'unable_to_find_awakened_obelisk_different_floor'      => 'Impossible d\'importer l\'Obélisque éveillé :name, il est sur un étage différent de l\'Obélisque lui-même. Keystone.guru ne le prend pas en charge pour le moment.',
                'unable_to_find_awakened_obelisks'                     => 'Impossible de trouver des Obélisques éveillés pour votre combinaison donjon/semaine. Vos sauts d\'Obélisque éveillé ne seront pas importés.',
                'unable_to_find_enemies_pull_skipped'                  => 'Échec à trouver des ennemis a entraîné un pull sauté.',
                'unable_to_find_enemies_pull_skipped_details'          => 'Cela peut indiquer que MDT a récemment eu une mise à jour qui n\'est pas encore intégrée dans Keystone.guru.',
                'unable_to_find_floor_for_object'                      => 'Impossible de trouver l\'étage de Keystone.guru qui correspond à l\'ID d\'étage MDT %d.',
                'unable_to_find_floor_for_object_details'              => 'Cela indique que MDT a un étage que Keystone.guru n\'a pas.',
                'unable_to_find_kg_equivalent_for_mdt_enemy'           => 'Impossible de trouver l\'équivalent Keystone.guru pour l\'ennemi MDT %s avec le PNJ %s (id: %s).',
                'unable_to_find_kg_equivalent_for_mdt_enemy_details'   => 'Cela indique que votre itinéraire tue un ennemi dont le PNJ est connu de Keystone.guru, mais Keystone.guru n\'a pas encore cartographié cet ennemi.',
                'unable_to_find_mdt_enemy_for_clone_index'             => 'Impossible de trouver l\'ennemi MDT pour l\'index de clone %s et l\'index de PNJ %s.',
                'unable_to_find_mdt_enemy_for_clone_index_details'     => 'Cela indique que MDT a cartographié un ennemi qui n\'est pas encore connu dans Keystone.guru.',
                'unable_to_validate_mdt_import_string'                 => 'Impossible de valider la chaîne d\'importation MDT',
            ],
        ],
    ],
    'npcservice' => [
        'all_dungeons' => 'Tous les donjons',
    ],
];
