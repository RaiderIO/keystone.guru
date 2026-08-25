<?php

return [

    'home' => [
        'front_page' => 'Keystone.guru',
        'compendium' => [
            'npc'          => 'Compendio de NPC',
            'npc_show'     => ':name',
            'spell'        => 'Compendio de hechizos',
            'spell_show'   => ':name',
            'activity'     => 'Actividad del compendio',
            'activity_day' => ':date',
            'tuning'       => '',
            'class'        => 'Por clase',
        ],
        'affixes' => 'Afijos',
        'about'   => 'Acerca de',
        'credits' => 'Créditos',
        'legal'   => [
            'cookies' => 'Cookies',
            'privacy' => 'Privacidad',
            'terms'   => 'Términos',
        ],
        'routes'              => 'Rutas',
        'routes_expansion'    => 'rutas :expansion',
        'routes_game_version' => 'rutas :gameVersion',
        'gameversion'         => [
            'update'  => ':gameVersion',
            'dungeon' => [
                'heatmap' => 'Mapa de calor',
                'explore' => 'Explorar',
            ],
        ],
        'dungeonroute' => [
            'new' => 'Nueva ruta',
        ],
        'dungeonroutes' => [
            'search'        => 'Buscar',
            'popular'       => 'Popular',
            'new'           => 'Nuevo',
            'routes_season' => 'Temporada :season',
            'season'        => [
                'popular' => 'Popular',
                'new'     => 'Nuevo',
            ],
            'discoverdungeon' => [
                'popular' => 'Popular',
                'new'     => 'Nuevo',
            ],
        ],
        'my_favorites'     => 'Mis favoritos',
        'account_settings' => 'Configuración de la cuenta',
        'my_routes'        => 'Mis rutas',
        'my_tags'          => 'Mis etiquetas',
        'my_teams'         => 'Mis equipos',
        'overview'         => 'Visión general',
        'new_team'         => 'Nuevo equipo',
        'edit_team'        => 'Editar equipo',
        'join_team'        => 'Unirse al equipo',
        'admin'            => [
            'admin'   => 'Admin',
            'affixes' => [
                'affixes'    => 'Afijos',
                'new_affix'  => 'Nuevo afijo',
                'edit_affix' => 'Editar :affix',
            ],
            'seasons' => [
                'seasons'     => 'Temporadas',
                'new_season'  => 'Nueva temporada',
                'edit_season' => 'Editar :season',
            ],
            'affixgroups' => [
                'new_affixgroup'  => 'Nuevo grupo de afijos',
                'edit_affixgroup' => 'Editar grupo de afijos',
            ],
            'tools' => [
                'admin_tools'                                 => 'Herramientas de admin',
                'view_exported_dungeondata'                   => 'Ver datos de mazmorra exportados',
                'select_exception'                            => 'Seleccionar excepción',
                'mdt_diff'                                    => 'Diferencia MDT',
                'view_mdt_string_contents'                    => 'Ver contenidos de cadena MDT',
                'import_npcs'                                 => 'Importar NPCs',
                'spells_missing_info'                         => 'Hechizos sin información',
                'npcs_missing_display_id'                     => 'NPCs sin ID de visualización',
                'thumbnails_regenerate'                       => 'Regenerar vistas previas',
                'combat_log_regenerate'                       => 'Regenerar rutas ARC',
                'combat_log_criteria'                         => 'Criterios de análisis del Compendio de NPC',
                'combat_log_run_data'                         => 'Purgar datos de ejecución del registro de combate',
                'combat_log_route_coverage'                   => 'Cobertura de fuerzas enemigas del ARC',
                'dungeonroute_view'                           => 'Ver ruta de mazmorra',
                'dungeonroute_view_contents'                  => 'Contenido de la ruta',
                'dungeonroute_mapping_version_usage'          => 'Uso de la versión de mapeo',
                'enemyforces_import'                          => 'Importar fuerzas enemigas',
                'enemyforces_recalculate'                     => 'Recalcular fuerzas enemigas',
                'features_list'                               => 'Funciones',
                'mdt_dungeon_mapping_hash'                    => 'Hash de mapeo de mazmorra',
                'mdt_dungeon_mapping_version_accuracy'        => 'Precisión de la versión de mapeo de mazmorra',
                'mdt_dungeon_mapping_version_to_mdt_mapping'  => 'Versión de mapeo de mazmorra a mapeo MDT',
                'mdt_dungeonroute'                            => 'Ruta de mazmorra MDT',
                'mdt_list'                                    => 'Lista de cadenas MDT',
                'messagebanner_set'                           => 'Establecer banner de mensaje',
                'npc_manage_spell_visibility'                 => 'Administrar visibilidad de hechizos de NPC',
                'wagogg_import_ingame_coordinates'            => 'Importar coordenadas del juego',
                'artisancommands_backfill_kill_zone_enemy_id' => 'Rellenar IDs de enemigo de zona de muerte',
            ],
            'expansions' => [
                'expansions'     => 'Expansiones',
                'new_expansion'  => 'Nueva expansión',
                'edit_expansion' => 'Editar expansión',
            ],
            'dungeons' => [
                'dungeons'     => 'Mazmorras',
                'new_dungeon'  => 'Nueva mazmorra',
                'edit_dungeon' => 'Editar :dungeon',
            ],
            'floors' => [
                'new_floor'  => 'Nuevo piso',
                'edit_floor' => 'Editar piso',
            ],
            'dungeonspeedrunrequirednpc' => [
                'new_dungeonspeedrunrequirednpc' => 'Nuevo NPC requerido para carrera rápida :difficulty',
            ],
            'npcs' => [
                'npcs'     => 'NPCs',
                'new_npc'  => 'Nuevo NPC',
                'edit_npc' => 'Editar :npc',
            ],
            'npcenemyforces' => [
                'new_npc_enemy_forces'  => 'Nuevas fuerzas enemigas de NPC',
                'edit_npc_enemy_forces' => 'Editar fuerzas enemigas de NPC',
            ],
            'npchealth' => [
                'new_npc_health'  => 'Nueva salud de NPC',
                'edit_npc_health' => 'Editar salud de NPC',
            ],
            'spells' => [
                'spells'     => 'Hechizos',
                'new_spell'  => 'Nuevo hechizo',
                'edit_spell' => 'Editar hechizo',
            ],
            'users' => [
                'users' => 'Usuarios',
            ],
            'user_reports' => [
                'user_reports' => 'Informes de usuario',
            ],
        ],
    ],

];
