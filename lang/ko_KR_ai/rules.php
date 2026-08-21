<?php

return [

    'banned_ip_range_rule' => [
        'invalid'         => '유효한 IP 주소 또는 CIDR 범위가 아닙니다.',
        'range_too_broad' => '이 범위가 너무 넓습니다. 허용되는 최대 범위는 /:min입니다.',
        'self_lockout'    => '자신의 IP 주소가 포함된 범위는 차단할 수 없습니다.',
    ],
    'create_route_npc_chronological_rule' => [
        'message' => 'Npc(s) :npcs diedAt는 engagedAt보다 이전이어야 합니다!',
    ],
    'dungeon_route_level_rule' => [
        'message' => '키 레벨 범위를 선택해야 합니다.',
    ],
    'faction_selection_required_rule' => [
        'message' => '이 던전에 대한 진영을 선택해야 합니다.',
    ],
    'json_string_count_rule' => [
        'message_min' => 'Json 문자열은 최소 :min_count개의 요소를 포함해야 합니다.',
        'message_max' => 'Json 문자열은 최대 :max_count개의 요소를 포함할 수 있습니다.',
    ],
    'map_icon_type_role_check_rule' => [
        'message' => '해당 지도 아이콘 유형은 사용자의 접근 수준에서 사용할 수 없습니다.',
    ],

];
