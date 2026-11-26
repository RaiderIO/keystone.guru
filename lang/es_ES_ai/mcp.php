<?php

return [
    'error' => [
        'unauthenticated'                   => 'No autenticado. Conéctate con un token de acceso OAuth.',
        'feature_inactive'                  => 'El servidor MCP de keystone.guru no está disponible para tu cuenta.',
        'missing_scope'                     => 'A tu token de acceso le falta el permiso :scope que necesita esta herramienta. Vuelve a conectarte y concédelo.',
        'rate_limited'                      => 'Demasiadas solicitudes para esta herramienta. Inténtalo de nuevo en :seconds s.',
        'request_refused'                   => 'Se ha rechazado la solicitud.',
        'dungeon_not_found'                 => 'Ninguna mazmorra activa coincide con ":dungeon". Usa list_dungeons para ver las claves válidas.',
        'route_not_found'                   => 'No tienes ninguna ruta con la clave ":route". Usa list_my_routes para ver tus rutas.',
        'pull_not_found'                    => 'La ruta no tiene el pull :pull.',
        'unable_to_save_route'              => 'No se ha podido guardar la ruta.',
        'unable_to_save_pull'               => 'No se ha podido guardar el pull.',
        'unable_to_delete_pull'             => 'No se ha podido eliminar el pull.',
        'no_enemies_given'                  => 'Indica al menos un ID de pack o un ID de enemigo.',
        'unknown_pack_ids'                  => 'Estos IDs de pack no forman parte del mapeo de la mazmorra de la ruta: :ids.',
        'unknown_enemy_ids'                 => 'Estos IDs de enemigo no forman parte del mapeo de la mazmorra de la ruta: :ids.',
        'enemies_already_pulled'            => 'Estos enemigos ya están en otro pull (ID de enemigo => número de pull): :enemies. Quítalos primero de ese pull o déjalos fuera.',
        'unknown_spell_ids'                 => 'Estos IDs de hechizo no se pueden lanzar en un pull: :ids. Usa get_route_options para ver los hechizos que puede tener un pull.',
        'unknown_floor'                     => 'La planta :floor no es una planta de la mazmorra de la ruta. Usa list_dungeons para ver las plantas.',
        'unknown_dungeon_difficulty'        => 'La dificultad :difficulty no es una dificultad de speedrun de esta mazmorra. Usa list_dungeons para ver las dificultades de cada mazmorra.',
        'unknown_dungeon_floor'             => 'La planta :floor no es una planta de la mazmorra. Usa list_dungeons para ver las plantas.',
        'unknown_dungeon_start'             => 'El inicio de mazmorra :start no pertenece al mapeo de la mazmorra de la ruta. Usa get_route_options con la mazmorra para ver sus inicios.',
        'specialization_not_of_class'       => 'El miembro del grupo :member tiene una especialización de otra clase.',
        'specialization_not_of_spell_class' => 'La especialización :specialization no es una especialización de la clase :class. Usa get_route_options para ver las especializaciones de cada clase.',
        'reorder_mismatch'                  => 'El orden debe incluir cada ID de pull de la ruta exactamente una vez. IDs de pull de la ruta: :ids.',
    ],
];
