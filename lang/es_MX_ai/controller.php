<?php

return [

    'admintools'                  => [
        'error' => [
            'mdt_string_parsing_failed'           => 'El análisis de la cadena MDT falló. ¿Realmente pegaste una cadena MDT?',
            'mdt_string_format_not_recognized'    => 'El formato de la cadena MDT no fue reconocido.',
            'cli_weakauras_parser_not_found'      => 'cli_weakauras_parser no está instalado.',
            'invalid_mdt_string'                  => 'Cadena MDT inválida',
            'invalid_mdt_string_exception'        => 'Cadena MDT inválida: %s',
            'mdt_importer_not_configured'         => 'El importador MDT no está configurado correctamente. Por favor, contacte al administrador sobre este problema.',
            'mdt_unable_to_find_npc_for_id'       => 'No se puede encontrar NPC para id %d',
            'mdt_mismatched_health'               => 'NPC %s tiene valores de salud desajustados, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces'         => 'NPC %s tiene fuerzas enemigas desajustadas, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces_teeming' => 'NPC %s tiene fuerzas enemigas desajustadas en enjambre, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_count'          => 'NPC %s tiene un conteo de enemigos desajustado, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_type'           => 'NPC %s tiene un tipo de enemigo desajustado, MDT: %s, KG: %s',
            'mdt_invalid_category'                => 'Categoría inválida',
        ],
        'flash' => [
            'message_banner_set_successfully' => 'Banner de mensaje establecido con éxito',
            'thumbnail_regenerate_result'     => 'Se despacharon :success trabajos para rutas :total. :failed falló.',
            'caches_dropped_successfully'     => 'Cachés eliminadas con éxito',
            'releases_exported'               => 'Lanzamientos exportados',
            'exception'                       => 'Excepción lanzada en el panel de administración',
            'feature_toggle_activated'        => 'Función :feature ahora está activada',
            'feature_toggle_deactivated'      => 'Función :feature ahora está desactivada',
            'feature_forgotten'               => 'Función :feature olvidada exitosamente',
            'read_only_mode_disabled'         => 'Modo de solo lectura deshabilitado',
            'read_only_mode_enabled'          => 'Modo de solo lectura habilitado',
        ],
    ],
    'apidungeonroute'             => [
        'mdt_generate_error'  => 'Ocurrió un error al generar tu cadena MDT: %s',
        'mdt_generate_no_lua' => 'El importador MDT no está configurado correctamente. Por favor, contacte al administrador sobre este problema',
    ],
    'apiuserreport'               => [
        'error' => [
            'unable_to_update_user_report' => 'No se puede actualizar el informe del usuario',
            'unable_to_save_report'        => 'No se puede guardar el informe',
        ],
    ],
    'brushline'                   => [
        'error' => [
            'unable_to_save_brushline'   => 'No se puede guardar la línea',
            'unable_to_delete_brushline' => 'No se puede eliminar la línea',
        ],
    ],
    'dungeon'                     => [
        'flash' => [
            'dungeon_created' => 'Mazmorra creada',
            'dungeon_updated' => 'Mazmorra actualizada',
        ],
    ],
    'dungeonroute'                => [
        'unable_to_save' => 'No se puede guardar la ruta',
        'flash'          => [
            'route_cloned_successfully' => 'Ruta clonada exitosamente',
            'route_updated'             => 'Ruta actualizada',
            'route_created'             => 'Ruta creada',
        ],
    ],
    'dungeonroutediscover'        => [
        'popular'           => 'Rutas populares',
        'this_week_affixes' => 'Afijos de esta semana',
        'next_week_affixes' => 'Afijos de la próxima semana',
        'new'               => 'Nuevo',
        'season'            => [
            'popular'           => '%s rutas populares',
            'this_week_affixes' => '%s esta semana',
            'next_week_affixes' => '%s la próxima semana',
            'new'               => '%s nuevas rutas',
        ],
        'dungeon'           => [
            'popular'           => '%s rutas populares',
            'this_week_affixes' => '%s esta semana',
            'next_week_affixes' => '%s la próxima semana',
            'new'               => '%s nuevas rutas',
        ],
    ],
    'dungeonspeedrunrequirednpcs' => [
        'no_linked_npc' => 'No hay NPC vinculado',
        'flash'         => [
            'npc_added_successfully'   => 'NPC añadido con éxito',
            'npc_deleted_successfully' => 'NPC eliminado con éxito',
        ],
    ],
    'expansion'                   => [
        'flash' => [
            'unable_to_save_expansion' => 'No se puede guardar la expansión',
            'expansion_updated'        => 'Expansión actualizada',
            'expansion_created'        => 'Expansión creada',
        ],
    ],
    'generic'                     => [
        'error' => [
            'floor_not_found_in_dungeon' => 'El piso no es parte de la mazmorra',
            'not_found'                  => 'No encontrado',
        ],
    ],
    'oauthlogin'                  => [
        'flash' => [
            'registered_successfully' => 'Registrado con éxito. ¡Disfruta del sitio web!',
            'user_exists'             => 'Ya existe un usuario con el nombre de usuario %s. ¿Ya te registraste antes?',
            'email_exists'            => 'Ya existe un usuario con la dirección de correo electrónico %s. ¿Ya te registraste antes?',
            'permission_denied'       => 'No se puede registrar: la solicitud fue denegada. Por favor, inténtalo de nuevo.',
            'read_only_mode_enabled'  => 'El modo de solo lectura está habilitado. No puedes registrarte en este momento.',
        ],
    ],
    'register'                    => [
        'flash'                 => [
            'registered_successfully' => 'Registrado con éxito. ¡Disfruta del sitio web!',
        ],
        'legal_agreed_required' => 'Debes aceptar nuestros términos legales para registrarte.',
        'legal_agreed_accepted' => 'Debes aceptar nuestros términos legales para registrarte.',
    ],
    'release'                     => [
        'error' => [
            'unable_to_save_release' => 'No se puede guardar la versión',
        ],
        'flash' => [
            'release_updated'  => 'Versión actualizada',
            'release_created'  => 'Versión creada',
            'github_exception' => 'Ocurrió un error al comunicar con Github: :message',
        ],
    ],
    'mappingversion'              => [
        'created_successfully'      => '¡Nueva versión de mapeo añadida!',
        'created_bare_successfully' => '¡Nueva versión de mapeo básica añadida!',
        'deleted_successfully'      => 'Versión de mapeo eliminada con éxito',
    ],
    'mdtimport'                   => [
        'unknown_dungeon' => 'Mazmorra desconocida',
        'error'           => [
            'mdt_string_parsing_failed'             => 'El análisis de la cadena MDT falló. ¿Realmente pegaste una cadena MDT?',
            'mdt_string_format_not_recognized'      => 'El formato de la cadena MDT no fue reconocido.',
            'cli_weakauras_parser_not_found'        => 'cli_weakauras_parser no instalado.',
            'invalid_mdt_string_exception'          => 'Cadena MDT inválida: %s',
            'invalid_mdt_string'                    => 'Cadena MDT inválida',
            'mdt_importer_not_configured_properly'  => 'El importador MDT no está configurado correctamente. Por favor, contacta al administrador sobre este problema.',
            'cannot_create_route_must_be_logged_in' => 'Debes estar conectado para crear una ruta',
        ],
    ],
    'path'                        => [
        'error' => [
            'unable_to_save_path'   => 'No se puede guardar la ruta',
            'unable_to_delete_path' => 'No se puede eliminar la ruta',
        ],
    ],
    'patreon'                     => [
        'flash' => [
            'unlink_successful'       => 'Tu cuenta de Patreon se ha desvinculado con éxito.',
            'link_successful'         => 'Tu Patreon ha sido vinculado con éxito. ¡Gracias!',
            'patreon_session_expired' => 'Tu sesión de Patreon ha expirado. Por favor, inténtalo de nuevo.',
            'session_expired'         => 'Tu sesión ha expirado. Por favor, inténtalo de nuevo.',
            'patreon_error_occurred'  => 'Ocurrió un error del lado de Patreon. Por favor, inténtalo de nuevo más tarde.',
            'internal_error_occurred' => 'Ocurrió un error al procesar la respuesta de Patreon: parece estar mal formada. El error fue registrado y se solucionará. Por favor, inténtalo de nuevo más tarde.',
        ],
    ],
    'profile'                     => [
        'flash' => [
            'email_already_in_use'             => 'Ese nombre de usuario ya está en uso.',
            'username_already_in_use'          => 'Ese nombre de usuario ya está en uso.',
            'profile_updated'                  => 'Perfil actualizado',
            'unexpected_error_when_saving'     => 'Ocurrió un error inesperado al intentar guardar tu perfil',
            'privacy_settings_updated'         => 'Configuración de privacidad actualizada',
            'password_changed'                 => 'Contraseña cambiada',
            'new_password_equals_old_password' => 'La nueva contraseña es igual a la contraseña antigua',
            'new_passwords_do_not_match'       => 'Las nuevas contraseñas no coinciden',
            'current_password_is_incorrect'    => 'La contraseña actual es incorrecta',
            'tag_created_successfully'         => 'Etiqueta creada con éxito',
            'tag_already_exists'               => 'Esta etiqueta ya existe',
            'admins_cannot_delete_themselves'  => '¡Los administradores no pueden eliminarse a sí mismos!',
            'account_deleted_successfully'     => 'Cuenta eliminada con éxito.',
            'error_deleting_account'           => 'Ocurrió un error. Por favor, inténtalo de nuevo.',
        ],
    ],
    'spell'                       => [
        'error' => [
            'unable_to_save_spell' => 'No se puede guardar el hechizo',
        ],
        'flash' => [
            'spell_updated' => 'Hechizo actualizado',
            'spell_created' => 'Hechizo creado',
        ],
    ],
    'team'                        => [
        'flash' => [
            'team_updated'                        => 'Equipo actualizado',
            'team_created'                        => 'Equipo creado',
            'unable_to_find_team_for_invite_code' => 'No se puede encontrar un equipo asociado con este código de invitación',
            'invite_accept_success'               => '¡Éxito! Ahora eres miembro del equipo %s.',
            'tag_created_successfully'            => 'Etiqueta creada con éxito',
            'tag_already_exists'                  => 'Esta etiqueta ya existe',
        ],
    ],
    'user'                        => [
        'flash' => [
            'user_is_now_an_admin'              => 'El usuario :user ahora es un administrador',
            'user_is_no_longer_an_admin'        => 'El usuario :user ya no es un administrador',
            'user_is_now_a_user'                => 'El usuario :user ahora es un usuario',
            'user_is_now_a_role'                => 'El usuario :user ahora es un :role',
            'account_deleted_successfully'      => 'Cuenta eliminada con éxito.',
            'account_deletion_error'            => 'Ocurrió un error. Por favor, inténtalo de nuevo.',
            'user_is_not_a_patron'              => 'Este usuario no es un Patron.',
            'all_benefits_granted_successfully' => 'Todos los beneficios otorgados con éxito.',
            'error_granting_all_benefits'       => 'Ocurrió un error al intentar otorgar todos los beneficios.',
        ],
    ],

];
