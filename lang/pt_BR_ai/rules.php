<?php

return [

    'banned_ip_range_rule' => [
        'invalid'         => 'Este não é um endereço IP ou intervalo CIDR válido.',
        'range_too_broad' => 'Este intervalo é muito amplo - o intervalo mais amplo permitido é /:min.',
        'self_lockout'    => 'Você não pode banir um intervalo que inclua seu próprio endereço IP.',
    ],
    'create_route_npc_chronological_rule' => [
        'message' => 'Npc(s) :npcs diedAt deve ser antes de engagedAt!',
    ],
    'dungeon_route_level_rule' => [
        'message' => 'Você deve selecionar um intervalo de nível de chave.',
    ],
    'faction_selection_required_rule' => [
        'message' => 'Você precisa selecionar uma facção para esta masmorra.',
    ],
    'json_string_count_rule' => [
        'message_min' => 'A string Json deve ter pelo menos :min_count elementos.',
        'message_max' => 'A string Json deve ter no máximo :max_count elementos.',
    ],
    'map_icon_type_role_check_rule' => [
        'message' => 'Esse tipo de ícone de mapa não está disponível para seu nível de acesso.',
    ],

];
