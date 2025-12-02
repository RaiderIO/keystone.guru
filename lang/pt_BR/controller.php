<?php

return [
    'admintools' => [
        'error' => [
            'cli_weakauras_parser_not_found'      => 'cli_weakauras_parser não instalado.',
            'invalid_mdt_string'                  => 'String MDT inválida',
            'invalid_mdt_string_exception'        => 'String MDT inválida: %s',
            'mdt_importer_not_configured'         => 'Importador MDT não configurado corretamente. Por favor, entre em contato com o administrador sobre este problema.',
            'mdt_invalid_category'                => 'Categoria inválida',
            'mdt_mismatched_enemy_count'          => 'NPC %s tem contagem de inimigos incompatível, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces'         => 'NPC %s tem forças inimigas incompatíveis, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces_teeming' => 'NPC %s tem forças inimigas abundantes incompatíveis, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_type'           => 'NPC %s tem tipo de inimigo incompatível, MDT: %s, KG: %s',
            'mdt_mismatched_health'               => 'NPC %s tem valores de saúde incompatíveis, MDT: %s, KG: %s',
            'mdt_string_format_not_recognized'    => 'O formato da string MDT não foi reconhecido.',
            'mdt_string_parsing_failed'           => 'Falha ao analisar a string MDT. Você realmente colou uma string MDT?',
            'mdt_unable_to_find_npc_for_id'       => 'Não foi possível encontrar NPC para o id %d',
        ],
        'flash' => [
            'caches_dropped_successfully'     => 'Caches descartados com sucesso',
            'exception'                       => 'Exceção lançada no painel de administração',
            'feature_forgotten'               => 'Recurso :feature esquecido com sucesso',
            'feature_toggle_activated'        => 'Recurso :feature agora ativado',
            'feature_toggle_deactivated'      => 'Recurso :feature agora desativado',
            'message_banner_set_successfully' => 'Banner de mensagem configurado com sucesso',
            'read_only_mode_disabled'         => 'Modo somente leitura desativado',
            'read_only_mode_enabled'          => 'Modo somente leitura ativado',
            'releases_exported'               => 'Lançamentos exportados',
            'thumbnail_regenerate_result'     => 'Despachou :success trabalhos para rotas :total. :failed falhou.',
        ],
    ],
    'apidungeonroute' => [
        'mdt_generate_error'  => 'Ocorreu um erro ao gerar sua string MDT: %s',
        'mdt_generate_no_lua' => 'Importador MDT não configurado corretamente. Por favor, entre em contato com o administrador sobre este problema',
    ],
    'apiuserreport' => [
        'error' => [
            'unable_to_save_report'        => 'Não foi possível salvar o relatório',
            'unable_to_update_user_report' => 'Não foi possível atualizar o relatório do usuário',
        ],
    ],
    'brushline' => [
        'error' => [
            'unable_to_delete_brushline' => 'Não foi possível excluir a linha',
            'unable_to_save_brushline'   => 'Não foi possível salvar a linha',
        ],
    ],
    'dungeon' => [
        'flash' => [
            'dungeon_created' => 'Masmorra criada',
            'dungeon_updated' => 'Masmorra atualizada',
        ],
    ],
    'dungeonroute' => [
        'flash' => [
            'route_cloned_successfully' => 'Rota clonada com sucesso',
            'route_created'             => 'Rota criada',
            'route_updated'             => 'Rota atualizada',
        ],
        'unable_to_save' => 'Não foi possível salvar a rota',
    ],
    'dungeonroutediscover' => [
        'dungeon' => [
            'new'               => '%s novas rotas',
            'next_week_affixes' => '%s próxima semana',
            'popular'           => '%s rotas populares',
            'this_week_affixes' => '%s esta semana',
        ],
        'new'               => 'Novo',
        'next_week_affixes' => 'Afixos da próxima semana',
        'popular'           => 'Rotas populares',
        'season'            => [
            'new'               => '%s novas rotas',
            'next_week_affixes' => '%s próxima semana',
            'popular'           => '%s rotas populares',
            'this_week_affixes' => '%s esta semana',
        ],
        'this_week_affixes' => 'Afixos desta semana',
    ],
    'dungeonspeedrunrequirednpcs' => [
        'flash' => [
            'npc_added_successfully'   => 'NPC adicionado com sucesso',
            'npc_deleted_successfully' => 'NPC removido com sucesso',
        ],
        'no_linked_npc' => 'Nenhum NPC vinculado',
    ],
    'expansion' => [
        'flash' => [
            'expansion_created'        => 'Expansão criada',
            'expansion_updated'        => 'Expansão atualizada',
            'unable_to_save_expansion' => 'Não foi possível salvar a expansão',
        ],
    ],
    'generic' => [
        'error' => [
            'floor_not_found_in_dungeon' => 'Andar não faz parte da masmorra',
            'not_found'                  => 'Não encontrado',
        ],
    ],
    'mappingversion' => [
        'created_bare_successfully' => 'Nova versão de mapeamento nua adicionada!',
        'created_successfully'      => 'Nova versão de mapeamento adicionada!',
        'deleted_successfully'      => 'Versão de mapeamento excluída com sucesso',
    ],
    'mdtimport' => [
        'error' => [
            'cannot_create_route_must_be_logged_in' => 'Você deve estar logado para criar uma rota',
            'cli_weakauras_parser_not_found'        => 'cli_weakauras_parser não instalado.',
            'invalid_mdt_string'                    => 'String MDT inválida',
            'invalid_mdt_string_exception'          => 'String MDT inválida: %s',
            'mdt_importer_not_configured_properly'  => 'Importador MDT não configurado corretamente. Por favor, entre em contato com o administrador sobre este problema.',
            'mdt_string_format_not_recognized'      => 'O formato da string MDT não foi reconhecido.',
            'mdt_string_parsing_failed'             => 'Falha ao analisar a string MDT. Você realmente colou uma string MDT?',
        ],
        'unknown_dungeon' => 'Masmorra desconhecida',
    ],
    'oauthlogin' => [
        'flash' => [
            'email_exists'            => 'Já existe um usuário com o e-mail %s. Você já se registrou antes?',
            'permission_denied'       => 'Não foi possível registrar - a solicitação foi negada. Por favor, tente novamente.',
            'read_only_mode_enabled'  => 'Modo somente leitura está ativado. Você não pode se registrar neste momento.',
            'registered_successfully' => 'Registrado com sucesso. Aproveite o site!',
            'user_exists'             => 'Já existe um usuário com o nome de usuário %s. Você já se registrou antes?',
        ],
    ],
    'path' => [
        'error' => [
            'unable_to_delete_path' => 'Não foi possível excluir o caminho',
            'unable_to_save_path'   => 'Não foi possível salvar o caminho',
        ],
    ],
    'patreon' => [
        'flash' => [
            'internal_error_occurred' => 'Ocorreu um erro ao processar a resposta do Patreon - parece estar malformada. O erro foi registrado e será tratado. Por favor, tente novamente mais tarde.',
            'link_successful'         => 'Seu Patreon foi vinculado com sucesso. Obrigado!',
            'patreon_error_occurred'  => 'Ocorreu um erro no lado do Patreon. Por favor, tente novamente mais tarde.',
            'patreon_session_expired' => 'Sua sessão no Patreon expirou. Por favor, tente novamente.',
            'session_expired'         => 'Sua sessão expirou. Por favor, tente novamente.',
            'unlink_successful'       => 'Sua conta do Patreon foi desvinculada com sucesso.',
        ],
    ],
    'profile' => [
        'flash' => [
            'account_deleted_successfully'     => 'Conta excluída com sucesso.',
            'admins_cannot_delete_themselves'  => 'Administradores não podem se excluir!',
            'current_password_is_incorrect'    => 'Senha atual está incorreta',
            'email_already_in_use'             => 'Esse nome de usuário já está em uso.',
            'error_deleting_account'           => 'Ocorreu um erro. Por favor, tente novamente.',
            'new_password_equals_old_password' => 'Nova senha é igual à senha antiga',
            'new_passwords_do_not_match'       => 'As novas senhas não coincidem',
            'password_changed'                 => 'Senha alterada',
            'privacy_settings_updated'         => 'Configurações de privacidade atualizadas',
            'profile_updated'                  => 'Perfil atualizado',
            'tag_already_exists'               => 'Esta tag já existe',
            'tag_created_successfully'         => 'Tag criada com sucesso',
            'unexpected_error_when_saving'     => 'Ocorreu um erro inesperado ao tentar salvar seu perfil',
            'username_already_in_use'          => 'Esse nome de usuário já está em uso.',
        ],
    ],
    'register' => [
        'flash' => [
            'registered_successfully' => 'Registrado com sucesso. Aproveite o site!',
        ],
        'legal_agreed_accepted' => 'Você precisa concordar com nossos termos legais para se registrar.',
        'legal_agreed_required' => 'Você precisa concordar com nossos termos legais para se registrar.',
    ],
    'release' => [
        'error' => [
            'unable_to_save_release' => 'Não foi possível salvar a versão',
        ],
        'flash' => [
            'github_exception' => 'Ocorreu um erro ao comunicar com o Github: :message',
            'release_created'  => 'Versão criada',
            'release_updated'  => 'Versão atualizada',
        ],
    ],
    'spell' => [
        'error' => [
            'unable_to_save_spell' => 'Não foi possível salvar o feitiço',
        ],
        'flash' => [
            'spell_created' => 'Feitiço criado',
            'spell_updated' => 'Feitiço atualizado',
        ],
    ],
    'team' => [
        'flash' => [
            'invite_accept_success'               => 'Sucesso! Você agora é membro da equipe %s.',
            'tag_already_exists'                  => 'Esta tag já existe',
            'tag_created_successfully'            => 'Tag criada com sucesso',
            'team_created'                        => 'Equipe criada',
            'team_updated'                        => 'Equipe atualizada',
            'unable_to_find_team_for_invite_code' => 'Não foi possível encontrar uma equipe associada a este código de convite',
        ],
    ],
    'user' => [
        'flash' => [
            'account_deleted_successfully'      => 'Conta excluída com sucesso.',
            'account_deletion_error'            => 'Ocorreu um erro. Por favor, tente novamente.',
            'all_benefits_granted_successfully' => 'Todos os benefícios concedidos com sucesso.',
            'error_granting_all_benefits'       => 'Ocorreu um erro ao tentar conceder todos os benefícios.',
            'user_is_no_longer_an_admin'        => 'Usuário :user não é mais um admin',
            'user_is_not_a_patron'              => 'Este usuário não é um Patron.',
            'user_is_now_a_role'                => 'Usuário :user agora é :role',
            'user_is_now_a_user'                => 'Usuário :user agora é um usuário',
            'user_is_now_an_admin'              => 'Usuário :user agora é um administrador',
        ],
    ],
];
