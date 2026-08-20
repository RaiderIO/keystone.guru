<?php

return [

    'banned_ip_range_rule' => [
        'invalid'         => 'Dies ist keine gültige IP-Adresse oder CIDR-Range.',
        'range_too_broad' => 'Diese Range ist zu weit gefasst - die größte erlaubte Range ist /:min.',
        'self_lockout'    => 'Du kannst keine Range sperren, die deine eigene IP-Adresse enthält.',
    ],
    'create_route_npc_chronological_rule' => [
        'message' => 'Npc(s) :npcs diedAt muss vor engagedAt sein!',
    ],
    'dungeon_route_level_rule' => [
        'message' => 'Du musst einen Schlüsselstufenbereich auswählen.',
    ],
    'faction_selection_required_rule' => [
        'message' => 'Du musst eine Fraktion für diesen Dungeon auswählen.',
    ],
    'json_string_count_rule' => [
        'message_min' => 'Json-String muss mindestens :min_count Elemente enthalten.',
        'message_max' => 'Json-String darf höchstens :max_count Elemente enthalten.',
    ],
    'map_icon_type_role_check_rule' => [
        'message' => 'Dieser Kartensymboltyp ist für dein Zugriffslevel nicht verfügbar.',
    ],

];
