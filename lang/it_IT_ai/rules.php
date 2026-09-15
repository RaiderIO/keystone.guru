<?php

return [

    'banned_ip_range_rule' => [
        'invalid'         => 'Questo non è un indirizzo IP o un intervallo CIDR valido.',
        'range_too_broad' => 'Questo intervallo è troppo ampio - l\'intervallo più ampio consentito è /:min.',
        'self_lockout'    => 'Non puoi bannare un intervallo che include il tuo indirizzo IP.',
    ],
    'create_route_npc_chronological_rule' => [
        'message' => 'Npc(s) :npcs diedAt deve essere prima di engagedAt!',
    ],
    'dungeon_route_level_rule' => [
        'message' => 'Devi selezionare un intervallo di livelli chiave.',
    ],
    'faction_selection_required_rule' => [
        'message' => 'Devi selezionare una fazione per questo dungeon.',
    ],
    'json_string_count_rule' => [
        'message_min' => 'La stringa Json deve avere almeno :min_count elementi.',
        'message_max' => 'La stringa Json deve avere al massimo :max_count elementi.',
    ],
    'map_icon_type_role_check_rule' => [
        'message' => 'Quel tipo di icona mappa non è disponibile per il tuo livello di accesso.',
    ],

];
