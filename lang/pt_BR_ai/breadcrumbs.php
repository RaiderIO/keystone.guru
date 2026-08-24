<?php

return [

    'home' => [
        'front_page' => 'Keystone.guru',
        'compendium' => [
            'npc'          => 'Compêndio de NPCs',
            'npc_show'     => ':name',
            'spell'        => 'Compêndio de Feitiços',
            'spell_show'   => ':name',
            'activity'     => 'Atividade do Compêndio',
            'activity_day' => ':date',
            'class'        => 'Por Classe',
        ],
        'affixes' => 'Afixos',
        'about'   => 'Sobre',
        'credits' => 'Créditos',
        'legal'   => [
            'cookies' => 'Cookies',
            'privacy' => 'Privacidade',
            'terms'   => 'Termos',
        ],
        'routes'              => 'Rotas',
        'routes_expansion'    => ':expansion rotas',
        'routes_game_version' => ':gameVersion rotas',
        'gameversion'         => [
            'update'  => ':gameVersion',
            'dungeon' => [
                'heatmap' => 'Mapa de Calor',
                'explore' => 'Explorar',
            ],
        ],
        'dungeonroute' => [
            'new' => 'Nova rota',
        ],
        'dungeonroutes' => [
            'search'        => 'Pesquisar',
            'popular'       => 'Popular',
            'new'           => 'Novo',
            'routes_season' => 'Temporada :season',
            'season'        => [
                'popular' => 'Popular',
                'new'     => 'Novo',
            ],
            'discoverdungeon' => [
                'popular' => 'Popular',
                'new'     => 'Novo',
            ],
        ],
        'my_favorites'     => 'Meus favoritos',
        'account_settings' => 'Configurações da conta',
        'my_routes'        => 'Minhas rotas',
        'my_tags'          => 'Minhas tags',
        'my_teams'         => 'Meus times',
        'overview'         => 'Visão geral',
        'new_team'         => 'Novo time',
        'edit_team'        => 'Editar equipe',
        'join_team'        => 'Entrar na equipe',
        'admin'            => [
            'admin'   => 'Admin',
            'affixes' => [
                'affixes'    => 'Afixos',
                'new_affix'  => 'Novo afixo',
                'edit_affix' => 'Editar :affix',
            ],
            'seasons' => [
                'seasons'     => 'Temporadas',
                'new_season'  => 'Nova temporada',
                'edit_season' => 'Editar :season',
            ],
            'affixgroups' => [
                'new_affixgroup'  => 'Novo grupo de afixos',
                'edit_affixgroup' => 'Editar grupo de afixos',
            ],
            'tools' => [
                'admin_tools'                                 => 'Ferramentas de Admin',
                'view_exported_dungeondata'                   => 'Ver dados de masmorra exportados',
                'select_exception'                            => 'Selecionar exceção',
                'mdt_diff'                                    => 'Diferença MDT',
                'view_mdt_string_contents'                    => 'Ver conteúdo da string MDT',
                'import_npcs'                                 => 'Importar NPCs',
                'spells_missing_info'                         => 'Feitiços sem informações',
                'npcs_missing_display_id'                     => 'NPCs sem ID de exibição',
                'thumbnails_regenerate'                       => 'Regenerar miniaturas',
                'combat_log_regenerate'                       => 'Regenerar rotas ARC',
                'combat_log_criteria'                         => 'Critérios de análise do Compêndio de NPCs',
                'combat_log_run_data'                         => 'Podar dados de execução de log de combate',
                'combat_log_route_coverage'                   => 'Cobertura de forças inimigas do ARC',
                'dungeonroute_view'                           => 'Ver rota de masmorra',
                'dungeonroute_view_contents'                  => 'Conteúdo da rota',
                'dungeonroute_mapping_version_usage'          => 'Uso da versão de mapeamento',
                'enemyforces_import'                          => 'Importar forças inimigas',
                'enemyforces_recalculate'                     => 'Recalcular forças inimigas',
                'features_list'                               => 'Recursos',
                'mdt_dungeon_mapping_hash'                    => 'Hash de mapeamento da masmorra',
                'mdt_dungeon_mapping_version_accuracy'        => 'Precisão da versão de mapeamento da masmorra',
                'mdt_dungeon_mapping_version_to_mdt_mapping'  => 'Mapeamento da versão de mapeamento da masmorra para o MDT',
                'mdt_dungeonroute'                            => 'Rota de masmorra do MDT',
                'mdt_list'                                    => 'Lista de strings do MDT',
                'messagebanner_set'                           => 'Definir banner de mensagem',
                'npc_manage_spell_visibility'                 => 'Gerenciar visibilidade de feitiços de NPC',
                'wagogg_import_ingame_coordinates'            => 'Importar coordenadas do jogo',
                'artisancommands_backfill_kill_zone_enemy_id' => 'Preencher IDs de inimigos de zona de morte',
            ],
            'expansions' => [
                'expansions'     => 'Expansões',
                'new_expansion'  => 'Nova expansão',
                'edit_expansion' => 'Editar expansão',
            ],
            'dungeons' => [
                'dungeons'     => 'Masmorras',
                'new_dungeon'  => 'Nova masmorra',
                'edit_dungeon' => 'Editar :dungeon',
            ],
            'floors' => [
                'new_floor'  => 'Novo andar',
                'edit_floor' => 'Editar andar',
            ],
            'dungeonspeedrunrequirednpc' => [
                'new_dungeonspeedrunrequirednpc' => 'Novo NPC obrigatório de corrida contra o tempo de :difficulty',
            ],
            'npcs' => [
                'npcs'     => 'NPCs',
                'new_npc'  => 'Novo NPC',
                'edit_npc' => 'Editar :npc',
            ],
            'npcenemyforces' => [
                'new_npc_enemy_forces'  => 'Novas forças inimigas de NPC',
                'edit_npc_enemy_forces' => 'Editar forças inimigas de NPC',
            ],
            'npchealth' => [
                'new_npc_health'  => 'Nova saúde do NPC',
                'edit_npc_health' => 'Editar saúde do NPC',
            ],
            'spells' => [
                'spells'     => 'Feitiços',
                'new_spell'  => 'Novo feitiço',
                'edit_spell' => 'Editar feitiço',
            ],
            'users' => [
                'users' => 'Usuários',
            ],
            'user_reports' => [
                'user_reports' => 'Relatórios de usuários',
            ],
        ],
    ],

];
