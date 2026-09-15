<?php

return [

    'banned_ip_range_rule' => [
        'invalid'         => 'Ce n\'est pas une adresse IP ou une plage CIDR valide.',
        'range_too_broad' => 'Cette plage est trop large - la plage maximale autorisée est /:min.',
        'self_lockout'    => 'Vous ne pouvez pas bannir une plage qui inclut votre propre adresse IP.',
    ],
    'create_route_npc_chronological_rule' => [
        'message' => 'Npc(s) :npcs diedAt doit être avant engagedAt !',
    ],
    'dungeon_route_level_rule' => [
        'message' => 'Vous devez sélectionner une plage de niveau de clé.',
    ],
    'faction_selection_required_rule' => [
        'message' => 'Vous devez sélectionner une faction pour ce donjon.',
    ],
    'json_string_count_rule' => [
        'message_min' => 'La chaîne JSON doit contenir au moins :min_count éléments.',
        'message_max' => 'La chaîne JSON doit contenir au plus :max_count éléments.',
    ],
    'map_icon_type_role_check_rule' => [
        'message' => 'Ce type d\'icône de carte n\'est pas disponible pour votre niveau d\'accès.',
    ],

];
