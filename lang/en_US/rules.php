<?php

return [

    'banned_ip_range_rule' => [
        'invalid'         => 'This is not a valid IP address or CIDR range.',
        'range_too_broad' => 'This range is too broad - the widest allowed range is /:min.',
        'self_lockout'    => 'You cannot ban a range that includes your own IP address.',
    ],
    'create_route_npc_chronological_rule' => [
        'message' => 'Npc(s) :npcs diedAt must be before engagedAt!',
    ],
    'dungeon_route_level_rule' => [
        'message' => 'You must select a key level range.',
    ],
    'faction_selection_required_rule' => [
        'message' => 'You need to select a faction for this dungeon.',
    ],
    'json_string_count_rule' => [
        'message_min' => 'Json string must have at least :min_count elements.',
        'message_max' => 'Json string must have at most :max_count elements.',
    ],
    'map_icon_type_role_check_rule' => [
        'message' => 'That map icon type is not available for your access level.',
    ],

];
