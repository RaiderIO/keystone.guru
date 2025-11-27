<?php

return [
    'combatlogservice' => [
        'analyze_combat_log' => [
            'processing_error' => 'No se puede procesar el registro de combate: error.',
            'verify_error'     => 'No se puede verificar el registro de combate: error.',
        ],
    ],
    'mdt' => [
        'io' => [
            'export_string' => [
                'category' => [
                    'map_icon' => 'Icono de mapa',
                    'pull'     => 'Tirada %d',
                    'title'    => 'Título',
                ],
                'map_icon_contains_non_ascii_char_bug' => 'Uno de tus comentarios en un icono de mapa tiene caracteres no ASCII que se sabe que provocan un error de codificación aún no resuelto en Keystone.guru. Tu comentario de mapa ha sido despojado de todos los caracteres ofensivos, nos disculpamos por el inconveniente y esperamos resolver este problema pronto.',
                'map_icon_contains_non_ascii_char_bug_details' => 'Comentario anterior: "%s", nuevo comentario: "%s"',
                'route_title_contains_non_ascii_char_bug' => 'El título de tu ruta contiene caracteres no ASCII que se sabe que provocan un error de codificación aún no resuelto en Keystone.guru.
                                                        Tu título de ruta ha sido despojado de todos los caracteres ofensivos, nos disculpamos por el inconveniente y esperamos resolver este problema pronto.',
                'route_title_contains_non_ascii_char_bug_details' => 'Título anterior: %s, nuevo título: %s',
                'unable_to_find_mdt_enemy_for_kg_caused_empty_pull' => 'Esta tirada ha sido eliminada ya que no se pudieron encontrar todos los enemigos seleccionados en MDT, resultando en una tirada vacía.',
                'unable_to_find_mdt_enemy_for_kg_enemy' => 'No se puede encontrar el equivalente MDT para el enemigo de Keystone.guru con NPC %s (enemy_id: %d, npc_id: %d).',
                'unable_to_find_mdt_enemy_for_kg_enemy_details' => 'Esto indica que tu ruta mata a un enemigo cuyo NPC es conocido por MDT, pero Keystone.guru aún no ha acoplado ese enemigo a un equivalente en MDT (o no existe en MDT).',
            ],
            'import_string' => [
                'category' => [
                    'awakened_obelisks' => 'Obeliscos Despertados',
                    'notes'             => 'Notas',
                    'object'            => 'Objeto %d',
                    'pull'              => 'Tirada %d',
                    'pulls'             => 'Tiradas',
                ],
                'limit_reached_brushlines' => 'No se puede importar la ruta: más del máximo de líneas :limit.',
                'limit_reached_notes' => 'No se puede importar la ruta: más del máximo de notas :limit.',
                'limit_reached_paths' => 'No se puede importar la ruta: más del máximo de caminos :limit.',
                'limit_reached_pulls' => 'No se puede importar la ruta: más del máximo de tiradas :limit.',
                'object_out_of_bounds' => 'No se puede colocar el comentario: no se pudo colocar el comentario ":comment" el objeto está fuera de límites.',
                'unable_to_decode_mdt_import_string' => 'No se puede decodificar la cadena de importación MDT',
                'unable_to_find_awakened_enemy_for_final_boss' => 'No se puede encontrar el Enemigo Despertado %s (%s) en el jefe final en %s.',
                'unable_to_find_awakened_enemy_for_final_boss_details' => 'Esto indica que Keystone.guru tiene un error de mapeo que necesitará ser corregido. Envía la advertencia anterior a mí y la corregiré.',
                'unable_to_find_awakened_obelisk_different_floor' => 'No se puede importar el Obelisco Despertado :name, está en un piso diferente al del propio Obelisco. Keystone.guru no admite esto en este momento.',
                'unable_to_find_awakened_obelisks' => 'No se pueden encontrar Obeliscos Despertados para tu combinación de calabozo/semana. Tus saltos de Obelisco Despertado no se importarán.',
                'unable_to_find_enemies_pull_skipped' => 'La imposibilidad de encontrar enemigos resultó en que una tirada fuera omitida.',
                'unable_to_find_enemies_pull_skipped_details' => 'Esto puede indicar que MDT recientemente tuvo una actualización que aún no está integrada en Keystone.guru.',
                'unable_to_find_floor_for_object' => 'No se puede encontrar el piso de Keystone.guru que coincida con el ID de piso de MDT %d.',
                'unable_to_find_floor_for_object_details' => 'Esto indica que MDT tiene un piso que Keystone.guru no tiene.',
                'unable_to_find_kg_equivalent_for_mdt_enemy' => 'No se puede encontrar un equivalente de Keystone.guru para el enemigo MDT %s con NPC %s (id: %s).',
                'unable_to_find_kg_equivalent_for_mdt_enemy_details' => 'Esto indica que tu ruta mata a un enemigo cuyo NPC es conocido por Keystone.guru, pero Keystone.guru aún no tiene mapeado a ese enemigo.',
                'unable_to_find_mdt_enemy_for_clone_index' => 'No se puede encontrar el enemigo MDT para el índice de clon %s y el índice de npc %s.',
                'unable_to_find_mdt_enemy_for_clone_index_details' => 'Esto indica que MDT ha mapeado un enemigo que aún no es conocido en Keystone.guru.',
                'unable_to_validate_mdt_import_string' => 'No se puede validar la cadena de importación MDT',
            ],
        ],
    ],
    'npcservice' => [
        'all_dungeons' => 'Todas las mazmorras',
    ],
];
