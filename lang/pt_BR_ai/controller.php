<?php

return [

    'admintools' => [
        'error' => [
            'mdt_string_parsing_failed'           => 'Falha ao analisar a string MDT. Você realmente colou uma string MDT?',
            'mdt_string_format_not_recognized'    => 'O formato da string MDT não foi reconhecido.',
            'cli_weakauras_parser_not_found'      => 'cli_weakauras_parser não instalado.',
            'invalid_mdt_string'                  => 'String MDT inválida',
            'invalid_mdt_string_exception'        => 'String MDT inválida: %s',
            'mdt_importer_not_configured'         => 'Importador MDT não configurado corretamente. Por favor, entre em contato com o administrador sobre este problema.',
            'mdt_unable_to_find_npc_for_id'       => 'Não foi possível encontrar NPC para o id %d',
            'mdt_mismatched_health'               => 'NPC %s tem valores de saúde incompatíveis, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces'         => 'NPC %s tem forças inimigas incompatíveis, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces_teeming' => 'NPC %s tem forças inimigas abundantes incompatíveis, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_count'          => 'NPC %s tem contagem de inimigos incompatível, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_type'           => 'NPC %s tem tipo de inimigo incompatível, MDT: %s, KG: %s',
            'mdt_invalid_category'                => 'Categoria inválida',
        ],
        'flash' => [
            'banned_ip_address_added'                => 'Endereço IP banido com sucesso',
            'banned_ip_address_removed'              => 'Banimento removido com sucesso',
            'message_banner_set_successfully'        => 'Banner de mensagem configurado com sucesso',
            'thumbnail_regenerate_result'            => 'Despachou :success trabalhos para rotas :total. :failed falhou.',
            'combatlog_route_regenerate_result'      => 'Despachados :count trabalhos',
            'combatlog_criteria_reset'               => 'Todas as contagens de critérios de análise de hoje foram redefinidas.',
            'combatlog_criteria_thresholds_updated'  => 'Os limites dos critérios de análise foram atualizados.',
            'caches_dropped_successfully'            => 'Caches descartados com sucesso',
            'caches_drop_queued'                     => 'O descarte de cache foi enfileirado e será executado em segundo plano',
            'exception'                              => 'Exceção lançada no painel de administração',
            'feature_toggle_activated'               => 'Recurso :feature agora ativado',
            'feature_toggle_deactivated'             => 'Recurso :feature agora desativado',
            'feature_forgotten'                      => 'Recurso :feature esquecido com sucesso',
            'mapping_version_upgrade_queued'         => 'Enfileiradas :count rotas para atualização de :version para a mais recente.',
            'mapping_version_upgrade_already_latest' => 'Esta versão de mapeamento já é a mais recente para sua masmorra — nenhuma rota foi enfileirada.',
            'read_only_mode_disabled'                => 'Modo somente leitura desativado',
            'read_only_mode_enabled'                 => 'Modo somente leitura ativado',
        ],
    ],
    'affix' => [
        'flash' => [
            'affix_created' => 'Afixo criado',
            'affix_updated' => 'Afixo atualizado',
        ],
    ],
    'affixgroup' => [
        'flash' => [
            'affixgroup_created' => 'Grupo de afixos criado',
            'affixgroup_updated' => 'Grupo de afixos atualizado',
            'affixgroup_deleted' => 'Grupo de afixos excluído',
        ],
    ],
    'apicombatlogroute' => [
        'error' => [
            'no_post_body' => '',
        ],
    ],
    'apicombatlogrun' => [
        'error' => [
            'no_segments' => 'Nenhum segmento de log de combate está disponível para esta execução.',
        ],
    ],
    'apidungeonroute' => [
        'mdt_generate_error'  => 'Ocorreu um erro ao gerar sua string MDT: %s',
        'mdt_generate_no_lua' => 'Importador MDT não configurado corretamente. Por favor, entre em contato com o administrador sobre este problema',
    ],
    'apiuserreport' => [
        'error' => [
            'unable_to_update_user_report' => 'Não foi possível atualizar o relatório do usuário',
            'unable_to_save_report'        => 'Não foi possível salvar o relatório',
        ],
    ],
    'brushline' => [
        'error' => [
            'unable_to_save_brushline'   => 'Não foi possível salvar a linha',
            'unable_to_delete_brushline' => 'Não foi possível excluir a linha',
        ],
    ],
    'arrow' => [
        'error' => [
            'unable_to_save_arrow'   => 'Não foi possível salvar a seta',
            'unable_to_delete_arrow' => 'Não foi possível excluir a seta',
        ],
    ],
    'dungeon' => [
        'flash' => [
            'dungeon_created' => 'Masmorra criada',
            'dungeon_updated' => 'Masmorra atualizada',
        ],
    ],
    'dungeonroute' => [
        'unable_to_save' => 'Não foi possível salvar a rota',
        'flash'          => [
            'route_cloned_successfully' => 'Rota clonada com sucesso',
            'route_updated'             => 'Rota atualizada',
            'route_created'             => 'Rota criada',
            'upgrade_draft_created'     => '',
            'upgrade_applied'           => '',
            'upgrade_discarded'         => '',
        ],
    ],
    'dungeonroutediscover' => [
        'popular' => 'Rotas populares',
        'new'     => 'Novo',
        'season'  => [
            'popular' => '%s rotas populares',
            'new'     => '%s novas rotas',
        ],
        'dungeon' => [
            'popular' => '%s rotas populares',
            'new'     => '%s novas rotas',
        ],
    ],
    'dungeonspeedrunrequirednpcs' => [
        'no_linked_npc' => 'Nenhum NPC vinculado',
        'flash'         => [
            'npc_added_successfully'   => 'NPC adicionado com sucesso',
            'npc_deleted_successfully' => 'NPC removido com sucesso',
        ],
    ],
    'expansion' => [
        'flash' => [
            'unable_to_save_expansion' => 'Não foi possível salvar a expansão',
            'expansion_updated'        => 'Expansão atualizada',
            'expansion_created'        => 'Expansão criada',
        ],
    ],
    'generic' => [
        'error' => [
            'floor_not_found_in_dungeon' => 'Andar não faz parte da masmorra',
            'not_found'                  => 'Não encontrado',
        ],
    ],
    'killzone' => [
        'error' => [
            'facade_location_not_convertible' => 'Não foi possível posicionar o pull aqui - esta localização não pertence a nenhum andar desta masmorra',
            'unable_to_delete_pull'           => '',
        ],
    ],
    'oauthlogin' => [
        'flash' => [
            'registered_successfully' => 'Registrado com sucesso. Aproveite o site!',
            'user_exists'             => 'Já existe um usuário com o nome de usuário %s. Você já se registrou antes?',
            'email_exists'            => 'Já existe um usuário com o e-mail %s. Você já se registrou antes?',
            'permission_denied'       => 'Não foi possível registrar - a solicitação foi negada. Por favor, tente novamente.',
            'read_only_mode_enabled'  => 'Modo somente leitura está ativado. Você não pode se registrar neste momento.',
        ],
    ],
    'register' => [
        'flash' => [
            'registered_successfully' => 'Registrado com sucesso. Aproveite o site!',
        ],
        'legal_agreed_required' => 'Você precisa concordar com nossos termos legais para se registrar.',
        'legal_agreed_accepted' => 'Você precisa concordar com nossos termos legais para se registrar.',
    ],
    'mappingversion' => [
        'created_successfully'      => 'Nova versão de mapeamento adicionada!',
        'created_bare_successfully' => 'Nova versão de mapeamento nua adicionada!',
        'deleted_successfully'      => 'Versão de mapeamento excluída com sucesso',
    ],
    'mdtimport' => [
        'unknown_dungeon' => 'Masmorra desconhecida',
        'error'           => [
            'mdt_string_parsing_failed'             => 'Falha ao analisar a string MDT. Você realmente colou uma string MDT?',
            'mdt_string_format_not_recognized'      => 'O formato da string MDT não foi reconhecido.',
            'cli_weakauras_parser_not_found'        => 'cli_weakauras_parser não instalado.',
            'invalid_mdt_string_exception'          => 'String MDT inválida: %s',
            'invalid_mdt_string'                    => 'String MDT inválida',
            'mdt_importer_not_configured_properly'  => 'Importador MDT não configurado corretamente. Por favor, entre em contato com o administrador sobre este problema.',
            'cannot_create_route_must_be_logged_in' => 'Você deve estar logado para criar uma rota',
        ],
    ],
    'path' => [
        'error' => [
            'unable_to_save_path'   => 'Não foi possível salvar o caminho',
            'unable_to_delete_path' => 'Não foi possível excluir o caminho',
        ],
    ],
    'patreon' => [
        'flash' => [
            'unlink_successful'       => 'Sua conta do Patreon foi desvinculada com sucesso.',
            'link_successful'         => 'Seu Patreon foi vinculado com sucesso. Obrigado!',
            'patreon_session_expired' => 'Sua sessão no Patreon expirou. Por favor, tente novamente.',
            'session_expired'         => 'Sua sessão expirou. Por favor, tente novamente.',
            'patreon_error_occurred'  => 'Ocorreu um erro no lado do Patreon. Por favor, tente novamente mais tarde.',
            'internal_error_occurred' => 'Ocorreu um erro ao processar a resposta do Patreon - parece estar malformada. O erro foi registrado e será tratado. Por favor, tente novamente mais tarde.',
        ],
    ],
    'profile' => [
        'flash' => [
            'email_already_in_use'             => 'Esse nome de usuário já está em uso.',
            'username_already_in_use'          => 'Esse nome de usuário já está em uso.',
            'profile_updated'                  => 'Perfil atualizado',
            'unexpected_error_when_saving'     => 'Ocorreu um erro inesperado ao tentar salvar seu perfil',
            'privacy_settings_updated'         => 'Configurações de privacidade atualizadas',
            'creator_profile_updated'          => 'Perfil de criador atualizado',
            'password_changed'                 => 'Senha alterada',
            'new_password_equals_old_password' => 'Nova senha é igual à senha antiga',
            'new_passwords_do_not_match'       => 'As novas senhas não coincidem',
            'current_password_is_incorrect'    => 'Senha atual está incorreta',
            'tag_created_successfully'         => 'Tag criada com sucesso',
            'tag_already_exists'               => 'Esta tag já existe',
            'admins_cannot_delete_themselves'  => 'Administradores não podem se excluir!',
            'account_deleted_successfully'     => 'Conta excluída com sucesso.',
            'error_deleting_account'           => 'Ocorreu um erro. Por favor, tente novamente.',
        ],
        'error' => [
            'add_ad_free_giveaway_limit_reached'        => 'Não foi possível adicionar mais brindes sem anúncios. Limite atingido.',
            'add_ad_free_giveaway_already_ad_free'      => 'Não foi possível adicionar brindes sem anúncios, o usuário já está sem anúncios através da própria assinatura do Patreon.',
            'add_ad_free_giveaway_already_has_giveaway' => 'Não foi possível adicionar brindes sem anúncios, o usuário já está sem anúncios através de um brinde existente.',
            'remove_ad_free_giveaway_not_found'         => 'Não foi possível remover o brinde sem anúncios - o usuário não possui nenhum no momento.',
            'remove_ad_free_giveaway_not_yours'         => 'Não é possível remover brindes sem anúncios que não foram originalmente concedidos por você.',
        ],
    ],
    'season' => [
        'flash' => [
            'season_created' => 'Temporada criada',
            'season_updated' => 'Temporada atualizada',
        ],
    ],
    'spell' => [
        'error' => [
            'unable_to_save_spell' => 'Não foi possível salvar o feitiço',
        ],
        'flash' => [
            'spell_updated' => 'Feitiço atualizado',
            'spell_created' => 'Feitiço criado',
        ],
    ],
    'team' => [
        'flash' => [
            'team_updated'                        => 'Equipe atualizada',
            'team_created'                        => 'Equipe criada',
            'unable_to_find_team_for_invite_code' => 'Não foi possível encontrar uma equipe associada a este código de convite',
            'invite_accept_success'               => 'Sucesso! Você agora é membro da equipe %s.',
            'tag_created_successfully'            => 'Tag criada com sucesso',
            'tag_already_exists'                  => 'Esta tag já existe',
        ],
    ],
    'user' => [
        'flash' => [
            'user_is_now_an_admin'              => 'Usuário :user agora é um administrador',
            'user_is_no_longer_an_admin'        => 'Usuário :user não é mais um admin',
            'user_is_now_a_user'                => 'Usuário :user agora é um usuário',
            'user_is_now_a_role'                => 'Usuário :user agora é :role',
            'account_deleted_successfully'      => 'Conta excluída com sucesso.',
            'account_deletion_error'            => 'Ocorreu um erro. Por favor, tente novamente.',
            'user_is_not_a_patron'              => 'Este usuário não é um Patron.',
            'all_benefits_granted_successfully' => 'Todos os benefícios concedidos com sucesso.',
            'error_granting_all_benefits'       => 'Ocorreu um erro ao tentar conceder todos os benefícios.',
        ],
    ],

    'admin' => [
        'dungeonroute' => [
            'flash' => [
                'updated' => 'Rota de masmorra atualizada com sucesso.',
                'deleted' => 'Rota de masmorra excluída com sucesso.',
                'claimed' => 'Rota de masmorra reivindicada com sucesso. Agora você é o proprietário.',
            ],
        ],
    ],

];
