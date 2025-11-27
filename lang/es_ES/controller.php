<?php

return [
    'admintools' => [
        'error' => [
            'cli_weakauras_parser_not_found'      => 'cli_weakauras_parser no está instalado.',
            'invalid_mdt_string'                  => 'Cadena MDT no válida',
            'invalid_mdt_string_exception'        => 'Cadena MDT no válida: %s',
            'mdt_importer_not_configured'         => 'El importador MDT no está configurado correctamente. Por favor, contacte al administrador sobre este problema.',
            'mdt_invalid_category'                => 'Categoría no válida',
            'mdt_mismatched_enemy_count'          => 'El NPC %s tiene un conteo de enemigos no coincidente, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces'         => 'El NPC %s tiene fuerzas enemigas no coincidentes, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces_teeming' => 'El NPC %s tiene fuerzas enemigas no coincidentes en enjambre, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_type'           => 'El NPC %s tiene un tipo de enemigo no coincidente, MDT: %s, KG: %s',
            'mdt_mismatched_health'               => 'El NPC %s tiene valores de salud no coincidentes, MDT: %s, KG: %s',
            'mdt_string_format_not_recognized'    => 'El formato de la cadena MDT no fue reconocido.',
            'mdt_string_parsing_failed'           => 'Falló el análisis de la cadena MDT. ¿Realmente pegaste una cadena MDT?',
            'mdt_unable_to_find_npc_for_id'       => 'No se pudo encontrar el NPC para el id %d',
        ],
        'flash' => [
            'caches_dropped_successfully'     => 'Cachés eliminados con éxito',
            'exception'                       => 'Excepción lanzada en el panel de administración',
            'feature_forgotten'               => 'Función :feature olvidada con éxito',
            'feature_toggle_activated'        => 'Función :feature ahora está activada',
            'feature_toggle_deactivated'      => 'Función :feature ahora está desactivada',
            'message_banner_set_successfully' => 'Banner de mensaje configurado con éxito',
            'read_only_mode_disabled'         => 'Modo de solo lectura desactivado',
            'read_only_mode_enabled'          => 'Modo de solo lectura activado',
            'releases_exported'               => 'Lanzamientos exportados',
            'thumbnail_regenerate_result'     => 'Se enviaron :success trabajos para :total rutas. :failed falló.',
        ],
    ],
    'apidungeonroute' => [
        'mdt_generate_error'  => 'Ocurrió un error al generar tu cadena MDT: %s',
        'mdt_generate_no_lua' => 'El importador MDT no está configurado correctamente. Por favor, contacte al administrador sobre este problema',
    ],
    'apiuserreport' => [
        'error' => [
            'unable_to_save_report'        => 'No se puede guardar el informe',
            'unable_to_update_user_report' => 'No se puede actualizar el informe del usuario',
        ],
    ],
    'brushline' => [
        'error' => [
            'unable_to_delete_brushline' => 'No se puede eliminar la línea',
            'unable_to_save_brushline'   => 'No se puede guardar la línea',
        ],
    ],
    'dungeon' => [
        'flash' => [
            'dungeon_created' => 'Mazmorra creada',
            'dungeon_updated' => 'Mazmorra actualizada',
        ],
    ],
    'dungeonroute' => [
        'flash'          => [
            'route_cloned_successfully' => 'Ruta clonada con éxito',
            'route_created'             => 'Ruta creada',
            'route_updated'             => 'Ruta actualizada',
        ],
        'unable_to_save' => 'No se puede guardar la ruta',
    ],
    'dungeonroutediscover' => [
        'dungeon' => [
            'new'               => '%s nuevas rutas',
            'next_week_affixes' => '%s la próxima semana',
            'popular'           => '%s rutas populares',
            'this_week_affixes' => '%s esta semana',
        ],
        'new'     => 'Nuevo',
        'next_week_affixes' => 'Afijos de la próxima semana',
        'popular' => 'Rutas populares',
        'season' => [
            'new'               => '%s nuevas rutas',
            'next_week_affixes' => '%s la próxima semana',
            'popular'           => '%s rutas populares',
            'this_week_affixes' => '%s esta semana',
        ],
        'this_week_affixes' => 'Afijos de esta semana',
    ],
    'dungeonspeedrunrequirednpcs' => [
        'flash'         => [
            'npc_added_successfully'   => 'NPC añadido con éxito',
            'npc_deleted_successfully' => 'NPC eliminado con éxito',
        ],
        'no_linked_npc' => 'No hay NPC vinculado',
    ],
    'expansion' => [
        'flash' => [
            'expansion_created'        => 'Expansión creada',
            'expansion_updated'        => 'Expansión actualizada',
            'unable_to_save_expansion' => 'No se pudo guardar la expansión',
        ],
    ],
    'generic' => [
        'error' => [
            'floor_not_found_in_dungeon' => 'El piso no es parte de la mazmorra',
            'not_found'                  => 'No encontrado',
        ],
    ],
    'mappingversion' => [
        'created_bare_successfully' => '¡Añadida nueva versión de mapeo básica!',
        'created_successfully'      => '¡Añadida nueva versión de mapeo!',
        'deleted_successfully'      => 'Versión de mapeo eliminada con éxito',
    ],
    'mdtimport' => [
        'error'           => [
            'cannot_create_route_must_be_logged_in' => 'Debe iniciar sesión para crear una ruta',
            'cli_weakauras_parser_not_found'        => 'cli_weakauras_parser no está instalado.',
            'invalid_mdt_string'                    => 'Cadena MDT no válida',
            'invalid_mdt_string_exception'          => 'Cadena MDT no válida: %s',
            'mdt_importer_not_configured_properly'  => 'El importador MDT no está configurado correctamente. Por favor contacte al administrador sobre este problema.',
            'mdt_string_format_not_recognized'      => 'El formato de la cadena MDT no fue reconocido.',
            'mdt_string_parsing_failed'             => 'El análisis de la cadena MDT falló. ¿Realmente pegaste una cadena MDT?',
        ],
        'unknown_dungeon' => 'Mazmorra desconocida',
    ],
    'oauthlogin' => [
        'flash' => [
            'email_exists'            => 'Ya existe un usuario con la dirección de correo electrónico %s. ¿Ya se registró antes?',
            'permission_denied'       => 'No se pudo registrar - la solicitud fue denegada. Por favor intente de nuevo.',
            'read_only_mode_enabled'  => 'El modo de solo lectura está habilitado. No puede registrarse en este momento.',
            'registered_successfully' => 'Registrado con éxito. ¡Disfruta del sitio web!',
            'user_exists'             => 'Ya existe un usuario con el nombre de usuario %s. ¿Ya se registró antes?',
        ],
    ],
    'path' => [
        'error' => [
            'unable_to_delete_path' => 'No se pudo eliminar el camino',
            'unable_to_save_path'   => 'No se pudo guardar el camino',
        ],
    ],
    'patreon' => [
        'flash' => [
            'internal_error_occurred' => 'Ocurrió un error al procesar la respuesta de Patreon - parece estar mal formada. El error fue registrado y será atendido. Por favor intente de nuevo más tarde.',
            'link_successful'         => 'Tu Patreon ha sido vinculado con éxito. ¡Gracias!',
            'patreon_error_occurred'  => 'Ocurrió un error en el lado de Patreon. Por favor intente de nuevo más tarde.',
            'patreon_session_expired' => 'Tu sesión de Patreon ha expirado. Por favor intente de nuevo.',
            'session_expired'         => 'Tu sesión ha expirado. Por favor intente de nuevo.',
            'unlink_successful'       => 'Tu cuenta de Patreon ha sido desvinculada con éxito.',
        ],
    ],
    'profile' => [
        'flash' => [
            'account_deleted_successfully'     => 'Cuenta eliminada con éxito.',
            'admins_cannot_delete_themselves'  => '¡Los administradores no pueden eliminarse a sí mismos!',
            'current_password_is_incorrect'    => 'La contraseña actual es incorrecta',
            'email_already_in_use'             => 'Ese nombre de usuario ya está en uso.',
            'error_deleting_account'           => 'Ocurrió un error. Por favor intente de nuevo.',
            'new_password_equals_old_password' => 'La nueva contraseña es igual a la antigua',
            'new_passwords_do_not_match'       => 'Las nuevas contraseñas no coinciden',
            'password_changed'                 => 'Contraseña cambiada',
            'privacy_settings_updated'         => 'Configuraciones de privacidad actualizadas',
            'profile_updated'                  => 'Perfil actualizado',
            'tag_already_exists'               => 'Esta etiqueta ya existe',
            'tag_created_successfully'         => 'Etiqueta creada con éxito',
            'unexpected_error_when_saving'     => 'Ocurrió un error inesperado al intentar guardar tu perfil',
            'username_already_in_use'          => 'Ese nombre de usuario ya está en uso.',
        ],
    ],
    'register' => [
        'flash'                 => [
            'registered_successfully' => 'Registrado con éxito. ¡Disfruta del sitio web!',
        ],
        'legal_agreed_accepted' => 'Tiene que aceptar nuestros términos legales para registrarse.',
        'legal_agreed_required' => 'Tiene que aceptar nuestros términos legales para registrarse.',
    ],
    'release' => [
        'error' => [
            'unable_to_save_release' => 'No se pudo guardar la versión',
        ],
        'flash' => [
            'github_exception' => 'Ocurrió un error al comunicarse con Github: :message',
            'release_created'  => 'Versión creada',
            'release_updated'  => 'Versión actualizada',
        ],
    ],
    'spell' => [
        'error' => [
            'unable_to_save_spell' => 'No se pudo guardar el hechizo',
        ],
        'flash' => [
            'spell_created' => 'Hechizo creado',
            'spell_updated' => 'Hechizo actualizado',
        ],
    ],
    'team' => [
        'flash' => [
            'invite_accept_success'               => '¡Éxito! Ahora eres miembro del equipo %s.',
            'tag_already_exists'                  => 'Esta etiqueta ya existe',
            'tag_created_successfully'            => 'Etiqueta creada con éxito',
            'team_created'                        => 'Equipo creado',
            'team_updated'                        => 'Equipo actualizado',
            'unable_to_find_team_for_invite_code' => 'No se pudo encontrar un equipo asociado con este código de invitación',
        ],
    ],
    'user' => [
        'flash' => [
            'account_deleted_successfully'      => 'Cuenta eliminada con éxito.',
            'account_deletion_error'            => 'Ocurrió un error. Por favor intente de nuevo.',
            'all_benefits_granted_successfully' => 'Todos los beneficios otorgados con éxito.',
            'error_granting_all_benefits'       => 'Ocurrió un error al intentar otorgar todos los beneficios.',
            'user_is_no_longer_an_admin'        => 'El usuario :user ya no es administrador',
            'user_is_not_a_patron'              => 'Este usuario no es un Patron.',
            'user_is_now_a_role'                => 'El usuario :user ahora es un :role',
            'user_is_now_a_user'                => 'El usuario :user ahora es un usuario',
            'user_is_now_an_admin'              => 'El usuario :user ahora es un administrador',
        ],
    ],
];
