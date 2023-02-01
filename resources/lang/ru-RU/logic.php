<?php

return [
    'mdt' => [
        'io' => [
            'export_string' => [
                'category'                                          => [
                    'pull'     => 'Пулл %d',
                    'title'    => 'Название',
                    'map_icon' => 'Иконки карты',
                ],
                'unable_to_find_mdt_enemy_for_kg_enemy'             => 'Невозможно найти врага в MDT антологичного для  Keystone.guru с NPC %s (id_вгара: %d, npc_id: %d)',
                'unable_to_find_mdt_enemy_for_kg_enemy_details'     => 'Ваш маршрут содержит врага, который известен MDT, но Keystone.guru еще не связал этого врага с его аналогом в MDT (или его нет в MDT).',
                'unable_to_find_mdt_enemy_for_kg_caused_empty_pull' => 'Пул был удален так как все выбранные враги не найдены в MDT, в противном случае это пустой пул.',
                'route_title_contains_non_ascii_char_bug'           => 'Название вашего маршрута содержит символы, отличные от ascii, которые, как известно, вызывают еще не устраненную ошибку кодирования в Keystone.guru. В названии вашего маршрута удалены все неподдерживаемые символы. Приносим извинения за неудобства и надеемся вскоре решить эту проблему.',
                'route_title_contains_non_ascii_char_bug_details'   => 'Старое название: %s, Новое название: %s',
                'map_icon_contains_non_ascii_char_bug'              => 'Один из ваших комментариев к иконке на карте содержит символы, отличные от ascii, которые, как известно, вызывают еще не устраненную ошибку кодирования в Keystone.guru. В вашем комментарии к иконке на карте удалены все неподдерживаемые символы. Приносим извинения за неудобства и надеемся вскоре решить эту проблему.',
                'map_icon_contains_non_ascii_char_bug_details'      => 'Старый комментарий: "%s", Новый комментарий: "%s"',
            ],
            'import_string' => [
                'category'                                             => [
                    'pull'   => 'Пулл %s',
                    'object' => '@todo ru: .mdt.io.import_string.category.object',
                ],
                'unable_to_find_floor_for_object'                      => '@todo ru: .mdt.io.import_string.unable_to_find_floor_for_object',
                'unable_to_find_floor_for_object_details'              => '@todo ru: .mdt.io.import_string.unable_to_find_floor_for_object_details',
                'unable_to_find_mdt_enemy_for_clone_index'             => 'Невозможно найти врага MDT для клона индекса %s и NPC индекса %s.',
                'unable_to_find_mdt_enemy_for_clone_index_details'     => 'MDT нанес на карту врага, который еще не известен в Keystone.guru.',
                'unable_to_find_kg_equivalent_for_mdt_enemy'           => 'Невозможно найти аналог врага %s Keystone.guru для MDT NPC %s (id:%s)',
                'unable_to_find_kg_equivalent_for_mdt_enemy_details'   => 'В вашем маршруте выбран враг, который известен Keystone.guru, но Keystone.guru еще не нанес на карту этого врага на карту.',
                'unable_to_find_awakened_enemy_for_final_boss'         => 'Невозможно найти Пробужденного врага %s (%s) у последнего босса в %s',
                'unable_to_find_awakened_enemy_for_final_boss_details' => 'В Keystone.guru есть ошибка сопоставления, которую необходимо исправить. Отправьте администратору вышеприведенное предупреждение, чтоб исправить его.',
                'unable_to_find_enemies_pull_skipped'                  => 'Ошибка в поиске врагов привела к пропуску пака.',
                'unable_to_find_enemies_pull_skipped_details'          => 'Эта ошибка может означать, что у MDT недавно было обновление, которое еще не интегрировано в Keystone.guru.',
            ],
        ],
    ],
];
