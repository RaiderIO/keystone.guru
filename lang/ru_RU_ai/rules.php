<?php

return [

    'banned_ip_range_rule' => [
        'invalid'         => 'Это не является допустимым IP-адресом или диапазоном CIDR.',
        'range_too_broad' => 'Этот диапазон слишком широк - максимально допустимый диапазон /:min.',
        'self_lockout'    => 'Вы не можете заблокировать диапазон, который включает ваш собственный IP-адрес.',
    ],
    'create_route_npc_chronological_rule' => [
        'message' => 'Npc(s) :npcs diedAt должен быть раньше engagedAt!',
    ],
    'dungeon_route_level_rule' => [
        'message' => 'Вы должны выбрать диапазон уровней ключа.',
    ],
    'faction_selection_required_rule' => [
        'message' => 'Вам нужно выбрать фракцию для этого подземелья.',
    ],
    'json_string_count_rule' => [
        'message_min' => 'JSON-строка должна содержать не менее :min_count элементов.',
        'message_max' => 'JSON-строка должна содержать не более :max_count элементов.',
    ],
    'map_icon_type_role_check_rule' => [
        'message' => 'Этот тип иконки карты недоступен для вашего уровня доступа.',
    ],

];
