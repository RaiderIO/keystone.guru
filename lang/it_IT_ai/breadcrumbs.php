<?php

return [

    'home' => [
        'front_page' => 'Keystone.guru',
        'compendium' => [
            'npc'          => 'Compendio NPC',
            'npc_show'     => ':name',
            'spell'        => 'Compendio incantesimi',
            'spell_show'   => ':name',
            'activity'     => 'Attività del compendio',
            'activity_day' => ':date',
            'class'        => 'Per classe',
        ],
        'affixes' => 'Affissi',
        'about'   => 'Informazioni',
        'credits' => 'Crediti',
        'legal'   => [
            'cookies' => 'Cookie',
            'privacy' => 'Privacy',
            'terms'   => 'Termini',
        ],
        'routes'              => 'Percorsi',
        'routes_expansion'    => ':expansion percorsi',
        'routes_game_version' => ':gameVersion percorsi',
        'gameversion'         => [
            'update'  => ':gameVersion',
            'dungeon' => [
                'heatmap' => 'Heatmap',
                'explore' => 'Esplora',
            ],
        ],
        'dungeonroute' => [
            'new' => 'Nuova rotta',
        ],
        'dungeonroutes' => [
            'search'        => 'Cerca',
            'popular'       => 'Popolare',
            'new'           => 'Nuovo',
            'routes_season' => 'Stagione :season',
            'season'        => [
                'popular' => 'Popolare',
                'new'     => 'Nuovo',
            ],
            'discoverdungeon' => [
                'popular' => 'Popolare',
                'new'     => 'Nuovo',
            ],
        ],
        'my_favorites'     => 'I miei preferiti',
        'account_settings' => 'Impostazioni account',
        'my_routes'        => 'Le mie rotte',
        'my_tags'          => 'I miei tag',
        'my_teams'         => 'Le mie squadre',
        'overview'         => 'Panoramica',
        'new_team'         => 'Nuova squadra',
        'edit_team'        => 'Modifica squadra',
        'join_team'        => 'Unisciti alla squadra',
        'admin'            => [
            'admin'   => 'Amministratore',
            'affixes' => [
                'affixes'    => 'Affissi',
                'new_affix'  => 'Nuovo affisso',
                'edit_affix' => 'Modifica :affix',
            ],
            'seasons' => [
                'seasons'     => 'Stagioni',
                'new_season'  => 'Nuova stagione',
                'edit_season' => 'Modifica :season',
            ],
            'affixgroups' => [
                'new_affixgroup'  => 'Nuovo gruppo di affissi',
                'edit_affixgroup' => 'Modifica gruppo di affissi',
            ],
            'tools' => [
                'admin_tools'                                 => 'Strumenti amministratore',
                'view_exported_dungeondata'                   => 'Visualizza dati spedizione esportati',
                'select_exception'                            => 'Seleziona eccezione',
                'mdt_diff'                                    => 'Differenza MDT',
                'view_mdt_string_contents'                    => 'Visualizza contenuti stringa MDT',
                'import_npcs'                                 => 'Importa NPC',
                'spells_missing_info'                         => 'Incantesimi con informazioni mancanti',
                'npcs_missing_display_id'                     => 'NPC mancanti dell\'ID di visualizzazione',
                'thumbnails_regenerate'                       => 'Rigenera miniature',
                'combat_log_regenerate'                       => 'Rigenera percorsi ARC',
                'combat_log_criteria'                         => 'Criteri di analisi del Compendio NPC',
                'combat_log_run_data'                         => 'Elimina dati dei log di combattimento delle run',
                'dungeonroute_view'                           => 'Visualizza percorso del dungeon',
                'dungeonroute_view_contents'                  => 'Contenuti del percorso',
                'dungeonroute_mapping_version_usage'          => 'Utilizzo della versione di mappatura',
                'enemyforces_import'                          => 'Importa forze nemiche',
                'enemyforces_recalculate'                     => 'Ricalcola forze nemiche',
                'features_list'                               => 'Funzionalità',
                'mdt_dungeon_mapping_hash'                    => 'Hash di mappatura del dungeon',
                'mdt_dungeon_mapping_version_accuracy'        => 'Precisione della versione di mappatura del dungeon',
                'mdt_dungeon_mapping_version_to_mdt_mapping'  => 'Versione di mappatura del dungeon verso mappatura MDT',
                'mdt_dungeonroute'                            => 'Percorso MDT del dungeon',
                'mdt_list'                                    => 'Elenco stringhe MDT',
                'messagebanner_set'                           => 'Imposta banner di messaggio',
                'npc_manage_spell_visibility'                 => 'Gestisci visibilità incantesimi NPC',
                'wagogg_import_ingame_coordinates'            => 'Importa coordinate di gioco',
                'artisancommands_backfill_kill_zone_enemy_id' => 'Popola ID nemici delle zone di uccisione',
            ],
            'expansions' => [
                'expansions'     => 'Espansioni',
                'new_expansion'  => 'Nuova espansione',
                'edit_expansion' => 'Modifica espansione',
            ],
            'dungeons' => [
                'dungeons'     => 'Spedizioni',
                'new_dungeon'  => 'Nuova spedizione',
                'edit_dungeon' => 'Modifica :dungeon',
            ],
            'floors' => [
                'new_floor'  => 'Nuovo piano',
                'edit_floor' => 'Modifica piano',
            ],
            'dungeonspeedrunrequirednpc' => [
                'new_dungeonspeedrunrequirednpc' => 'Nuovo NPC richiesto per speedrun :difficulty',
            ],
            'npcs' => [
                'npcs'     => 'NPC',
                'new_npc'  => 'Nuovo NPC',
                'edit_npc' => 'Modifica :npc',
            ],
            'npcenemyforces' => [
                'new_npc_enemy_forces'  => 'Nuove forze nemiche NPC',
                'edit_npc_enemy_forces' => 'Modifica forze nemiche NPC',
            ],
            'npchealth' => [
                'new_npc_health'  => 'Nuova salute NPC',
                'edit_npc_health' => 'Modifica salute NPC',
            ],
            'spells' => [
                'spells'     => 'Incantesimi',
                'new_spell'  => 'Nuovo incantesimo',
                'edit_spell' => 'Modifica incantesimo',
            ],
            'users' => [
                'users' => 'Utenti',
            ],
            'user_reports' => [
                'user_reports' => 'Rapporti utente',
            ],
        ],
    ],

];
