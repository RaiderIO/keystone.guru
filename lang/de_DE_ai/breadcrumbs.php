<?php

return [

    'home' => [
        'front_page' => 'Keystone.guru',
        'compendium' => [
            'npc'          => 'NPC-Kompendium',
            'npc_show'     => ':name',
            'spell'        => 'Zauber-Kompendium',
            'spell_show'   => ':name',
            'activity'     => 'Kompendium-Aktivität',
            'activity_day' => ':date',
            'tuning'       => 'Zauber-Anpassungen',
            'class'        => 'Nach Klasse',
        ],
        'affixes' => 'Affixe',
        'about'   => 'Über',
        'credits' => 'Credits',
        'legal'   => [
            'cookies' => 'Cookies',
            'privacy' => 'Datenschutz',
            'terms'   => 'Bedingungen',
        ],
        'routes'              => 'Routen',
        'routes_expansion'    => ':expansion Routen',
        'routes_game_version' => ':gameVersion Routen',
        'gameversion'         => [
            'update'  => ':gameVersion',
            'dungeon' => [
                'heatmap' => 'Heatmap',
                'explore' => 'Erkunden',
            ],
        ],
        'dungeonroute' => [
            'new' => 'Neue Route',
        ],
        'dungeonroutes' => [
            'search'        => 'Suche',
            'popular'       => 'Beliebt',
            'new'           => 'Neu',
            'routes_season' => 'Saison :season',
            'season'        => [
                'popular' => 'Beliebt',
                'new'     => 'Neu',
            ],
            'discoverdungeon' => [
                'popular' => 'Beliebt',
                'new'     => 'Neu',
            ],
        ],
        'my_favorites'     => 'Meine Favoriten',
        'account_settings' => 'Kontoeinstellungen',
        'my_routes'        => 'Meine Routen',
        'my_tags'          => 'Meine Tags',
        'my_teams'         => 'Meine Teams',
        'overview'         => 'Übersicht',
        'new_team'         => 'Neues Team',
        'edit_team'        => 'Team bearbeiten',
        'join_team'        => 'Team beitreten',
        'admin'            => [
            'admin'   => 'Admin',
            'affixes' => [
                'affixes'    => 'Affixe',
                'new_affix'  => 'Neues Affix',
                'edit_affix' => ':affix bearbeiten',
            ],
            'seasons' => [
                'seasons'     => 'Saisons',
                'new_season'  => 'Neue Saison',
                'edit_season' => ':season bearbeiten',
            ],
            'affixgroups' => [
                'new_affixgroup'  => 'Neue Affix-Gruppe',
                'edit_affixgroup' => 'Affix-Gruppe bearbeiten',
            ],
            'tools' => [
                'admin_tools'                                 => 'Admin-Tools',
                'view_exported_dungeondata'                   => 'Exportierte Dungeondaten anzeigen',
                'select_exception'                            => 'Ausnahme auswählen',
                'mdt_diff'                                    => 'MDT-Diff',
                'view_mdt_string_contents'                    => 'MDT-String-Inhalte anzeigen',
                'import_npcs'                                 => 'NPCs importieren',
                'spells_missing_info'                         => 'Zauber fehlen Informationen',
                'npcs_missing_display_id'                     => 'NPCs fehlen Anzeige-ID',
                'thumbnails_regenerate'                       => 'Vorschaubilder neu generieren',
                'combat_log_regenerate'                       => 'ARC-Routen neu generieren',
                'combat_log_criteria'                         => 'Parsing-Kriterien des NPC-Kompendiums',
                'combat_log_run_data'                         => 'Combat-Log-Run-Daten bereinigen',
                'combat_log_route_coverage'                   => 'ARC-Abdeckung der Feindkräfte',
                'dungeonroute_view'                           => 'Dungeon-Route ansehen',
                'dungeonroute_view_contents'                  => 'Routeninhalt',
                'dungeonroute_mapping_version_usage'          => 'Verwendung der Mapping-Version',
                'enemyforces_import'                          => 'Feindkräfte importieren',
                'enemyforces_recalculate'                     => 'Feindkräfte neu berechnen',
                'features_list'                               => 'Features',
                'mdt_dungeon_mapping_hash'                    => 'Dungeon-Mapping-Hash',
                'mdt_dungeon_mapping_version_accuracy'        => 'Genauigkeit der Dungeon-Mapping-Version',
                'mdt_dungeon_mapping_version_to_mdt_mapping'  => 'Dungeon-Mapping-Version zu MDT-Mapping',
                'mdt_dungeonroute'                            => 'MDT-Dungeon-Route',
                'mdt_list'                                    => 'MDT-String-Liste',
                'messagebanner_set'                           => 'Nachrichtenbanner setzen',
                'npc_manage_spell_visibility'                 => 'NPC-Zaubersichtbarkeit verwalten',
                'wagogg_import_ingame_coordinates'            => 'Ingame-Koordinaten importieren',
                'artisancommands_backfill_kill_zone_enemy_id' => 'Kill-Zone-Gegner-IDs nachtragen',
            ],
            'expansions' => [
                'expansions'     => 'Erweiterungen',
                'new_expansion'  => 'Neue Erweiterung',
                'edit_expansion' => 'Erweiterung bearbeiten',
            ],
            'dungeons' => [
                'dungeons'     => 'Dungeons',
                'new_dungeon'  => 'Neuer Dungeon',
                'edit_dungeon' => 'Bearbeite :dungeon',
            ],
            'floors' => [
                'new_floor'  => 'Neue Etage',
                'edit_floor' => 'Etage bearbeiten',
            ],
            'dungeonspeedrunrequirednpc' => [
                'new_dungeonspeedrunrequirednpc' => 'Neuer für :difficulty Dungeon-Speedrun benötigter NPC',
            ],
            'npcs' => [
                'npcs'     => 'NPCs',
                'new_npc'  => 'Neuer NPC',
                'edit_npc' => 'Bearbeite :npc',
            ],
            'npcenemyforces' => [
                'new_npc_enemy_forces'  => 'Neue NPC-Feindkräfte',
                'edit_npc_enemy_forces' => 'NPC-Feindkräfte bearbeiten',
            ],
            'npchealth' => [
                'new_npc_health'  => 'Neue NPC-Gesundheit',
                'edit_npc_health' => 'NPC-Gesundheit bearbeiten',
            ],
            'spells' => [
                'spells'     => 'Zauber',
                'new_spell'  => 'Neuer Zauber',
                'edit_spell' => 'Zauber bearbeiten',
            ],
            'users' => [
                'users' => 'Benutzer',
            ],
            'user_reports' => [
                'user_reports' => 'Benutzerberichte',
            ],
        ],
    ],

];
