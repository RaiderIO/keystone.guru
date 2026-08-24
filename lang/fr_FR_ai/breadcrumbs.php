<?php

return [

    'home' => [
        'front_page' => 'Keystone.guru',
        'compendium' => [
            'npc'          => 'Compendium des PNJ',
            'npc_show'     => ':name',
            'spell'        => 'Compendium des sorts',
            'spell_show'   => ':name',
            'activity'     => 'Activité du compendium',
            'activity_day' => ':date',
            'class'        => 'Par classe',
        ],
        'affixes' => 'Affixes',
        'about'   => 'À propos',
        'credits' => 'Crédits',
        'legal'   => [
            'cookies' => 'Cookies',
            'privacy' => 'Confidentialité',
            'terms'   => 'Conditions',
        ],
        'routes'              => 'Itinéraires',
        'routes_expansion'    => ':expansion itinéraires',
        'routes_game_version' => ':gameVersion itinéraires',
        'gameversion'         => [
            'update'  => ':gameVersion',
            'dungeon' => [
                'heatmap' => 'Heatmap',
                'explore' => 'Explorer',
            ],
        ],
        'dungeonroute' => [
            'new' => 'Nouvelle route',
        ],
        'dungeonroutes' => [
            'search'        => 'Recherche',
            'popular'       => 'Populaire',
            'new'           => 'Nouveau',
            'routes_season' => 'Saison :season',
            'season'        => [
                'popular' => 'Populaire',
                'new'     => 'Nouveau',
            ],
            'discoverdungeon' => [
                'popular' => 'Populaire',
                'new'     => 'Nouveau',
            ],
        ],
        'my_favorites'     => 'Mes favoris',
        'account_settings' => 'Paramètres du compte',
        'my_routes'        => 'Mes routes',
        'my_tags'          => 'Mes tags',
        'my_teams'         => 'Mes équipes',
        'overview'         => 'Vue d\'ensemble',
        'new_team'         => 'Nouvelle équipe',
        'edit_team'        => 'Modifier l\'équipe',
        'join_team'        => 'Rejoindre l\'équipe',
        'admin'            => [
            'admin'   => 'Admin',
            'affixes' => [
                'affixes'    => 'Affixes',
                'new_affix'  => 'Nouvel affixe',
                'edit_affix' => 'Modifier :affix',
            ],
            'seasons' => [
                'seasons'     => 'Saisons',
                'new_season'  => 'Nouvelle saison',
                'edit_season' => 'Modifier :season',
            ],
            'affixgroups' => [
                'new_affixgroup'  => 'Nouveau groupe d\'affixes',
                'edit_affixgroup' => 'Modifier le groupe d\'affixes',
            ],
            'tools' => [
                'admin_tools'                                 => 'Outils d\'administration',
                'view_exported_dungeondata'                   => 'Voir les données de donjon exportées',
                'select_exception'                            => 'Sélectionner une exception',
                'mdt_diff'                                    => 'Différence MDT',
                'view_mdt_string_contents'                    => 'Voir le contenu de la chaîne MDT',
                'import_npcs'                                 => 'Importer des PNJ',
                'spells_missing_info'                         => 'Sorts manquant d\'informations',
                'npcs_missing_display_id'                     => 'PNJs manquant d\'ID d\'affichage',
                'thumbnails_regenerate'                       => 'Régénérer les vignettes',
                'combat_log_regenerate'                       => 'Régénérer les routes ARC',
                'combat_log_criteria'                         => 'Critères d\'analyse du compendium des PNJ',
                'combat_log_run_data'                         => 'Purger les données de run des journaux de combat',
                'combat_log_route_coverage'                   => 'Couverture des forces ennemies par l\'ARC',
                'dungeonroute_view'                           => 'Voir la route de donjon',
                'dungeonroute_view_contents'                  => 'Contenu de la route',
                'dungeonroute_mapping_version_usage'          => 'Utilisation de la version de mapping',
                'enemyforces_import'                          => 'Importer les forces ennemies',
                'enemyforces_recalculate'                     => 'Recalculer les forces ennemies',
                'features_list'                               => 'Fonctionnalités',
                'mdt_dungeon_mapping_hash'                    => 'Hash de mapping du donjon',
                'mdt_dungeon_mapping_version_accuracy'        => 'Précision de la version de mapping du donjon',
                'mdt_dungeon_mapping_version_to_mdt_mapping'  => 'Correspondance entre la version de mapping du donjon et MDT',
                'mdt_dungeonroute'                            => 'Route de donjon MDT',
                'mdt_list'                                    => 'Liste des chaînes MDT',
                'messagebanner_set'                           => 'Définir la bannière de message',
                'npc_manage_spell_visibility'                 => 'Gérer la visibilité des sorts des PNJ',
                'wagogg_import_ingame_coordinates'            => 'Importer les coordonnées en jeu',
                'artisancommands_backfill_kill_zone_enemy_id' => 'Renseigner rétroactivement les identifiants d\'ennemi des zones de kill',
            ],
            'expansions' => [
                'expansions'     => 'Extensions',
                'new_expansion'  => 'Nouvelle extension',
                'edit_expansion' => 'Modifier l\'extension',
            ],
            'dungeons' => [
                'dungeons'     => 'Donjons',
                'new_dungeon'  => 'Nouveau donjon',
                'edit_dungeon' => 'Modifier :dungeon',
            ],
            'floors' => [
                'new_floor'  => 'Nouvel étage',
                'edit_floor' => 'Modifier l\'étage',
            ],
            'dungeonspeedrunrequirednpc' => [
                'new_dungeonspeedrunrequirednpc' => 'Nouveau PNJ requis pour le speedrun :difficulty',
            ],
            'npcs' => [
                'npcs'     => 'PNJ',
                'new_npc'  => 'Nouveau PNJ',
                'edit_npc' => 'Modifier :npc',
            ],
            'npcenemyforces' => [
                'new_npc_enemy_forces'  => 'Nouvelles forces ennemies des PNJ',
                'edit_npc_enemy_forces' => 'Modifier les forces ennemies des PNJ',
            ],
            'npchealth' => [
                'new_npc_health'  => 'Nouvelle santé du PNJ',
                'edit_npc_health' => 'Modifier la santé du PNJ',
            ],
            'spells' => [
                'spells'     => 'Sorts',
                'new_spell'  => 'Nouveau sort',
                'edit_spell' => 'Modifier le sort',
            ],
            'users' => [
                'users' => 'Utilisateurs',
            ],
            'user_reports' => [
                'user_reports' => 'Rapports d\'utilisateur',
            ],
        ],
    ],

];
