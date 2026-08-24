<?php

return [

    'mdt' => [
        'io' => [
            'export_string' => [
                'category' => [
                    'pull'         => 'Jalar %d',
                    'title'        => 'Título',
                    'map_icon'     => 'Ícono de mapa',
                    'raid_markers' => 'Marcadores de banda',
                ],
                'unable_to_find_mdt_enemy_for_kg_enemy'             => 'No se puede encontrar un equivalente de MDT para el enemigo de Keystone.guru con NPC %s (enemy_id: %d, npc_id: %d).',
                'unable_to_find_mdt_enemy_for_kg_enemy_details'     => 'Esto indica que tu ruta mata a un enemigo cuyo NPC es conocido por MDT, pero Keystone.guru aún no ha acoplado a ese enemigo con un equivalente de MDT (o no existe en MDT).',
                'unable_to_find_mdt_enemy_for_kg_caused_empty_pull' => 'Este jalón ha sido eliminado ya que no se pudieron encontrar todos los enemigos seleccionados en MDT, resultando en un jalón vacío.',
                'unable_to_find_mdt_enemy_for_kg_raid_marker'       => 'No se pudo encontrar el equivalente de MDT para el enemigo con el marcador de banda %s (npc_id: %s).',
                'route_title_contains_non_ascii_char_bug'           => 'El título de tu ruta contiene caracteres no ASCII que se sabe desencadenan un error de codificación no resuelto en Keystone.guru.
                                                        El título de tu ruta ha sido despojado de todos los caracteres problemáticos, nos disculpamos por el inconveniente y esperamos resolver este problema pronto.',
                'route_title_contains_non_ascii_char_bug_details' => 'Título anterior: %s, nuevo título: %s',
                'map_icon_contains_non_ascii_char_bug'            => 'Uno de tus comentarios sobre un ícono de mapa tiene caracteres no ASCII que se sabe desencadenan un error de codificación no resuelto en Keystone.guru. Tu comentario de mapa ha sido despojado de todos los caracteres problemáticos, nos disculpamos por el inconveniente y esperamos resolver este problema pronto.',
                'map_icon_contains_non_ascii_char_bug_details'    => 'Comentario anterior: "%s", nuevo comentario: "%s"',
            ],
            'import_string' => [
                'category' => [
                    'awakened_obelisks' => 'Obeliscos Despertados',
                    'pulls'             => 'Jalones',
                    'notes'             => 'Notas',
                    'arrows'            => 'Flechas',
                    'pull'              => 'Jalar %d',
                    'object'            => 'Objeto %d',
                    'raid_markers'      => 'Marcadores de banda',
                ],
                'object_out_of_bounds'                                 => 'No se puede colocar el comentario: no se pudo colocar el comentario ":comment" el objeto está fuera de límites.',
                'limit_reached_pulls'                                  => 'No se puede importar la ruta: más del máximo de :limit jalones.',
                'limit_reached_brushlines'                             => 'No se puede importar la ruta: más del máximo de :limit líneas.',
                'limit_reached_paths'                                  => 'No se puede importar la ruta: más del máximo de :limit caminos.',
                'limit_reached_arrows'                                 => 'No se pudo importar la ruta: más del máximo de :limit flechas.',
                'limit_reached_notes'                                  => 'No se puede importar la ruta: más del máximo de :limit notas.',
                'unable_to_find_floor_for_object'                      => 'No se puede encontrar un piso de Keystone.guru que coincida con el ID de piso de MDT %d.',
                'unable_to_find_floor_for_object_details'              => 'Esto indica que MDT tiene un piso que Keystone.guru no tiene.',
                'unable_to_find_mdt_enemy_for_clone_index'             => 'No se puede encontrar un enemigo MDT para el índice de clon %s y el índice de npc %s.',
                'unable_to_find_mdt_enemy_for_clone_index_details'     => 'Esto indica que MDT ha mapeado a un enemigo que aún no es conocido en Keystone.guru.',
                'unable_to_find_kg_equivalent_for_mdt_enemy'           => 'No se puede encontrar un equivalente de Keystone.guru para el enemigo MDT %s con NPC %s (id: %s).',
                'unable_to_find_kg_equivalent_for_mdt_enemy_details'   => 'Esto indica que tu ruta mata a un enemigo cuyo NPC es conocido por Keystone.guru, pero Keystone.guru aún no ha mapeado a ese enemigo.',
                'unable_to_find_awakened_enemy_for_final_boss'         => 'No se puede encontrar el Enemigo Despertado %s (%s) en el jefe final en %s.',
                'unable_to_find_awakened_enemy_for_final_boss_details' => 'Esto indica que Keystone.guru tiene un error de mapeo que necesitará ser corregido. Envía la advertencia anterior y la corregiré.',
                'unable_to_find_enemies_pull_skipped'                  => 'La imposibilidad de encontrar enemigos resultó en un jalón omitido.',
                'unable_to_find_enemies_pull_skipped_details'          => 'Esto puede indicar que MDT tuvo una actualización reciente que aún no está integrada en Keystone.guru.',
                'unable_to_find_awakened_obelisks'                     => 'No se pueden encontrar Obeliscos Despertados para tu combinación de calabozo/semana. Tus saltos de Obelisco Despertado no se importarán.',
                'unable_to_find_awakened_obelisk_different_floor'      => 'No se puede importar el Obelisco Despertado :name, está en un piso diferente al del Obelisco mismo. Keystone.guru no admite esto en este momento.',
                'unable_to_find_awakened_obelisk_enemy'                => 'No se pudo importar el Obelisco Despertado :name; no se pudo resolver su enemigo con tu combinación de mazmorra/semana.',
                'unable_to_decode_mdt_import_string'                   => 'No se puede decodificar la cadena de importación MDT',
                'unable_to_validate_mdt_import_string'                 => 'No se puede validar la cadena de importación MDT',
            ],
        ],
    ],
    'npcservice' => [
        'all_dungeons' => 'Todas las mazmorras',
    ],
    'combatlogservice' => [
        'analyze_combat_log' => [
            'verify_error'     => 'No se puede verificar el registro de combate: error.',
            'processing_error' => 'No se puede procesar el registro de combate: error.',
        ],
    ],
    'combatlog' => [
        'enemy_failure_analysis' => [
            'verdict' => [
                'npc_not_mapped'       => 'NPC no mapeado',
                'no_enemy_in_range'    => 'Ningún enemigo dentro del alcance',
                'enemies_exhausted'    => 'Más en el juego que en el mapeo',
                'wrong_floor_artifact' => 'Probablemente un artefacto de piso equivocado',
            ],
            'suggestion' => [
                'npc_not_mapped'       => ':npc no está en esta versión de mapeo en absoluto, y aun así se combatió :count veces en :routes rutas aquí. Agrégalo (o su pack) en esta ubicación.',
                'no_enemy_in_range'    => 'El :npc mapeado más cercano (enemigo :enemy_id) está a :distance yardas, más allá del alcance de combate de :range yardas. Probablemente falte aquí un pack de :npc, o está mapeado en el lugar equivocado.',
                'enemies_exhausted'    => ':enemies :npc mapeados dentro del alcance, pero las rutas siguen fallando aquí unas :avg veces cada una: el juego probablemente tenga más :npc en este pack que el mapeo.',
                'wrong_floor_artifact' => 'Ningún :npc dentro del alcance en este piso, pero hay uno a menos de :distance yardas en otro piso. El piso registrado se deduce del npc anterior en el registro, así que lo más probable es que sea esa deducción: verifícalo antes de cambiar el mapeo.',
            ],
        ],
    ],

];
