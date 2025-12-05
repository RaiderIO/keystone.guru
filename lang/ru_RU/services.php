<?php

return [

    'mdt' => [
        'io' => [
            'export_string' => [
                'category' => [
                    'pull'     => 'Захват %d',
                    'title'    => 'Название',
                    'map_icon' => 'Иконка карты',
                ],
                'unable_to_find_mdt_enemy_for_kg_enemy'             => 'Невозможно найти эквивалент MDT для врага Keystone.guru с NPC %s (enemy_id: %d, npc_id: %d).',
                'unable_to_find_mdt_enemy_for_kg_enemy_details'     => 'Это указывает на то, что ваш маршрут убивает врага, NPC которого известен MDT, но Keystone.guru еще не связал этого врага с эквивалентом MDT (или он не существует в MDT).',
                'unable_to_find_mdt_enemy_for_kg_caused_empty_pull' => 'Этот захват был удален, так как все выбранные враги не были найдены в MDT, что привело к пустому захвату.',
                'route_title_contains_non_ascii_char_bug'           => 'Название вашего маршрута содержит не-ASCII символы, которые, как известно, вызывают еще не решенную ошибку кодировки в Keystone.guru.
                                                        Название вашего маршрута было очищено от всех нежелательных символов, приносим извинения за неудобства и надеемся скоро решить эту проблему.',
                'route_title_contains_non_ascii_char_bug_details' => 'Старое название: %s, новое название: %s',
                'map_icon_contains_non_ascii_char_bug'            => 'Один из ваших комментариев на иконке карты содержит не-ASCII символы, которые, как известно, вызывают еще не решенную ошибку кодировки в Keystone.guru. Ваш комментарий был очищен от всех нежелательных символов, приносим извинения за неудобства и надеемся скоро решить эту проблему.',
                'map_icon_contains_non_ascii_char_bug_details'    => 'Старый комментарий: "%s", новый комментарий: "%s"',
            ],
            'import_string' => [
                'category' => [
                    'awakened_obelisks' => 'Пробужденные обелиски',
                    'pulls'             => 'Захваты',
                    'notes'             => 'Заметки',
                    'pull'              => 'Захват %d',
                    'object'            => 'Объект %d',
                ],
                'object_out_of_bounds'                                 => 'Невозможно разместить комментарий: невозможно разместить комментарий ":comment", объект вне границ.',
                'limit_reached_pulls'                                  => 'Невозможно импортировать маршрут: превышено максимальное количество :limit подтягиваний.',
                'limit_reached_brushlines'                             => 'Невозможно импортировать маршрут: превышено максимальное количество :limit линий.',
                'limit_reached_paths'                                  => 'Невозможно импортировать маршрут: превышено максимальное количество :limit путей.',
                'limit_reached_notes'                                  => 'Невозможно импортировать маршрут: превышено максимальное количество :limit заметок.',
                'unable_to_find_floor_for_object'                      => 'Не удалось найти этаж Keystone.guru, соответствующий идентификатору этажа MDT %d.',
                'unable_to_find_floor_for_object_details'              => 'Это указывает на то, что MDT имеет этаж, которого нет в Keystone.guru.',
                'unable_to_find_mdt_enemy_for_clone_index'             => 'Не удалось найти врага MDT для индекса клона %s и индекса npc %s.',
                'unable_to_find_mdt_enemy_for_clone_index_details'     => 'Это указывает на то, что MDT отобразил врага, который еще не известен в Keystone.guru.',
                'unable_to_find_kg_equivalent_for_mdt_enemy'           => 'Невозможно найти эквивалент Keystone.guru для врага MDT %s с NPC %s (id: %s).',
                'unable_to_find_kg_equivalent_for_mdt_enemy_details'   => 'Это указывает на то, что ваш маршрут убивает врага, NPC которого известен Keystone.guru, но этот враг еще не отображен в Keystone.guru.',
                'unable_to_find_awakened_enemy_for_final_boss'         => 'Невозможно найти Пробужденного врага %s (%s) у финального босса в %s.',
                'unable_to_find_awakened_enemy_for_final_boss_details' => 'Это указывает на ошибку в карте Keystone.guru, которую нужно исправить. Отправьте вышеуказанное предупреждение мне, и я его исправлю.',
                'unable_to_find_enemies_pull_skipped'                  => 'Невозможность найти врагов привела к пропуску подтягивания.',
                'unable_to_find_enemies_pull_skipped_details'          => 'Это может указывать на то, что MDT недавно обновился, но еще не интегрирован в Keystone.guru.',
                'unable_to_find_awakened_obelisks'                     => 'Невозможно найти Пробужденные Обелиски для вашей комбинации подземелья/недели. Ваши пропуски Пробужденных Обелисков не будут импортированы.',
                'unable_to_find_awakened_obelisk_different_floor'      => 'Невозможно импортировать Пробужденный Обелиск :name, он находится на другом этаже, чем сам Обелиск. Keystone.guru в настоящее время это не поддерживает.',
                'unable_to_decode_mdt_import_string'                   => 'Невозможно декодировать строку импорта MDT',
                'unable_to_validate_mdt_import_string'                 => 'Невозможно подтвердить строку импорта MDT',
            ],
        ],
    ],
    'npcservice' => [
        'all_dungeons' => 'Все подземелья',
    ],
    'combatlogservice' => [
        'analyze_combat_log' => [
            'verify_error'     => 'Невозможно проверить журнал боя: ошибка.',
            'processing_error' => 'Невозможно обработать журнал боя: ошибка.',
        ],
    ],

];
