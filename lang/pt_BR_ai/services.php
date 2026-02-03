<?php

return [

    'mdt'              => [
        'io' => [
            'export_string' => [
                'category'                                          => [
                    'pull'     => 'Puxada %d',
                    'title'    => 'Título',
                    'map_icon' => 'Ícone do mapa',
                ],
                'unable_to_find_mdt_enemy_for_kg_enemy'             => 'Não foi possível encontrar o equivalente MDT para o inimigo do Keystone.guru com NPC %s (enemy_id: %d, npc_id: %d).',
                'unable_to_find_mdt_enemy_for_kg_enemy_details'     => 'Isso indica que sua rota mata um inimigo cujo NPC é conhecido pelo MDT, mas o Keystone.guru ainda não associou esse inimigo a um equivalente MDT (ou ele não existe no MDT).',
                'unable_to_find_mdt_enemy_for_kg_caused_empty_pull' => 'Esta puxada foi removida, pois todos os inimigos selecionados não puderam ser encontrados no MDT, resultando em uma puxada vazia.',
                'route_title_contains_non_ascii_char_bug'           => 'O título da sua rota contém caracteres não-ASCII que são conhecidos por acionar um bug de codificação ainda não resolvido no Keystone.guru.
                                                        O título da sua rota foi removido de todos os caracteres ofensivos, pedimos desculpas pelo inconveniente e esperamos resolver este problema em breve.',
                'route_title_contains_non_ascii_char_bug_details'   => 'Título antigo: %s, novo título: %s',
                'map_icon_contains_non_ascii_char_bug'              => 'Um dos seus comentários em um ícone de mapa contém caracteres não-ASCII que são conhecidos por acionar um bug de codificação ainda não resolvido no Keystone.guru. Seu comentário no mapa foi removido de todos os caracteres ofensivos, pedimos desculpas pelo inconveniente e esperamos resolver este problema em breve.',
                'map_icon_contains_non_ascii_char_bug_details'      => 'Comentário antigo: "%s", novo comentário: "%s"',
            ],
            'import_string' => [
                'category'                                             => [
                    'awakened_obelisks' => 'Obeliscos Despertados',
                    'pulls'             => 'Puxadas',
                    'notes'             => 'Notas',
                    'pull'              => 'Puxada %d',
                    'object'            => 'Objeto %d',
                ],
                'object_out_of_bounds'                                 => 'Não foi possível colocar o comentário: não foi possível colocar o comentário ":comment" objeto está fora dos limites.',
                'limit_reached_pulls'                                  => 'Não foi possível importar a rota: mais do que o máximo de :limit puxadas.',
                'limit_reached_brushlines'                             => 'Não foi possível importar a rota: mais do que o máximo de :limit linhas.',
                'limit_reached_paths'                                  => 'Não foi possível importar a rota: mais do que o máximo de :limit caminhos.',
                'limit_reached_notes'                                  => 'Não foi possível importar a rota: mais do que o máximo de :limit notas.',
                'unable_to_find_floor_for_object'                      => 'Não foi possível encontrar um andar do Keystone.guru que corresponda ao ID do andar do MDT %d.',
                'unable_to_find_floor_for_object_details'              => 'Isso indica que o MDT tem um andar que o Keystone.guru não possui.',
                'unable_to_find_mdt_enemy_for_clone_index'             => 'Não foi possível encontrar o inimigo MDT para o índice de clone %s e índice de npc %s.',
                'unable_to_find_mdt_enemy_for_clone_index_details'     => 'Isso indica que o MDT mapeou um inimigo que ainda não é conhecido no Keystone.guru.',
                'unable_to_find_kg_equivalent_for_mdt_enemy'           => 'Não foi possível encontrar o equivalente do Keystone.guru para o inimigo MDT %s com NPC %s (id: %s).',
                'unable_to_find_kg_equivalent_for_mdt_enemy_details'   => 'Isso indica que sua rota mata um inimigo cujo NPC é conhecido pelo Keystone.guru, mas o Keystone.guru ainda não mapeou esse inimigo.',
                'unable_to_find_awakened_enemy_for_final_boss'         => 'Não foi possível encontrar o Inimigo Despertado %s (%s) no chefe final em %s.',
                'unable_to_find_awakened_enemy_for_final_boss_details' => 'Isso indica que o Keystone.guru tem um erro de mapeamento que precisará ser corrigido. Envie o aviso acima para mim e eu o corrigirei.',
                'unable_to_find_enemies_pull_skipped'                  => 'Falha ao encontrar inimigos resultou em uma puxada ignorada.',
                'unable_to_find_enemies_pull_skipped_details'          => 'Isso pode indicar que o MDT teve uma atualização recente que ainda não foi integrada no Keystone.guru.',
                'unable_to_find_awakened_obelisks'                     => 'Não foi possível encontrar Obeliscos Despertos para a combinação da sua masmorra/semana. Seus saltos de Obelisco Desperto não serão importados.',
                'unable_to_find_awakened_obelisk_different_floor'      => 'Não foi possível importar o Obelisco Desperto :name, ele está em um andar diferente do próprio Obelisco. O Keystone.guru não suporta isso no momento.',
                'unable_to_decode_mdt_import_string'                   => 'Não foi possível decodificar a string de importação MDT',
                'unable_to_validate_mdt_import_string'                 => 'Não foi possível validar a string de importação do MDT',
            ],
        ],
    ],
    'npcservice'       => [
        'all_dungeons' => 'Todas as masmorras',
    ],
    'combatlogservice' => [
        'analyze_combat_log' => [
            'verify_error'     => 'Não foi possível verificar o registro de combate: erro.',
            'processing_error' => 'Não foi possível processar o registro de combate: erro.',
        ],
    ],

];
