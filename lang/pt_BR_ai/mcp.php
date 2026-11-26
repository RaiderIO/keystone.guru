<?php

return [
    'error' => [
        'unauthenticated'                   => 'Não autenticado. Conecte-se com um token de acesso OAuth.',
        'feature_inactive'                  => 'O servidor MCP do keystone.guru não está disponível para sua conta.',
        'missing_scope'                     => 'Seu token de acesso não tem a permissão :scope de que esta ferramenta precisa. Conecte-se novamente e conceda-a.',
        'rate_limited'                      => 'Muitas solicitações para esta ferramenta. Tente novamente em :seconds s.',
        'request_refused'                   => 'A solicitação foi recusada.',
        'dungeon_not_found'                 => 'Nenhuma masmorra ativa corresponde a ":dungeon". Use list_dungeons para ver as chaves válidas.',
        'route_not_found'                   => 'Você não tem nenhuma rota com a chave ":route". Use list_my_routes para ver suas rotas.',
        'pull_not_found'                    => 'A rota não tem o pull :pull.',
        'unable_to_save_route'              => 'Não foi possível salvar a rota.',
        'unable_to_save_pull'               => 'Não foi possível salvar o pull.',
        'unable_to_delete_pull'             => 'Não foi possível remover o pull.',
        'no_enemies_given'                  => 'Informe pelo menos um ID de pacote ou um ID de inimigo.',
        'unknown_pack_ids'                  => 'Estes IDs de pacote não fazem parte do mapeamento da masmorra da rota: :ids.',
        'unknown_enemy_ids'                 => 'Estes IDs de inimigo não fazem parte do mapeamento da masmorra da rota: :ids.',
        'enemies_already_pulled'            => 'Estes inimigos já estão em outro pull (ID do inimigo => número do pull): :enemies. Remova-os desse pull primeiro ou deixe-os de fora.',
        'unknown_spell_ids'                 => 'Estes IDs de feitiço não podem ser lançados em um pull: :ids. Use get_route_options para ver os feitiços que um pull pode ter.',
        'unknown_floor'                     => 'O andar :floor não é um andar da masmorra da rota. Use list_dungeons para ver os andares.',
        'unknown_dungeon_difficulty'        => 'A dificuldade :difficulty não é uma dificuldade de speedrun desta masmorra. Use list_dungeons para ver as dificuldades de cada masmorra.',
        'unknown_dungeon_floor'             => 'O andar :floor não é um andar da masmorra. Use list_dungeons para ver os andares.',
        'unknown_dungeon_start'             => 'O início de masmorra :start não pertence ao mapeamento da masmorra da rota. Use get_route_options com a masmorra para ver seus inícios.',
        'specialization_not_of_class'       => 'O membro do grupo :member tem uma especialização de outra classe.',
        'specialization_not_of_spell_class' => 'A especialização :specialization não é uma especialização da classe :class. Use get_route_options para ver as especializações de cada classe.',
        'reorder_mismatch'                  => 'A ordem deve listar cada ID de pull da rota exatamente uma vez. IDs de pull da rota: :ids.',
    ],
];
