<?php

return [

    'banned_ip_range_rule' => [
        'invalid'         => '这不是一个有效的 IP 地址或 CIDR 范围。',
        'range_too_broad' => '此范围过大——允许的最大范围是 /:min。',
        'self_lockout'    => '您不能封禁包含您自己 IP 地址的范围。',
    ],
    'create_route_npc_chronological_rule' => [
        'message' => 'Npc(s) :npcs 的 diedAt 必须在 engagedAt 之前！',
    ],
    'dungeon_route_level_rule' => [
        'message' => '您必须选择一个钥匙等级范围。',
    ],
    'faction_selection_required_rule' => [
        'message' => '您需要为此地下城选择一个阵营。',
    ],
    'json_string_count_rule' => [
        'message_min' => 'JSON 字符串必须至少包含 :min_count 个元素。',
        'message_max' => 'JSON 字符串最多只能包含 :max_count 个元素。',
    ],
    'map_icon_type_role_check_rule' => [
        'message' => '该地图图标类型不适用于您的访问级别。',
    ],

];
