<?php

return [

    'mdt' => [
        'io' => [
            'export_string' => [
                'category' => [
                    'pull'     => 'Сутичка %d',
                    'title'    => 'Назва',
                    'map_icon' => 'Значок',
                ],
                'unable_to_find_mdt_enemy_for_kg_enemy'             => 'Не вдалося знайти відповідного НІПа %s в MDT для ворога з Keystone.guru (enemy_id: %d, npc_id: %d).',
                'unable_to_find_mdt_enemy_for_kg_enemy_details'     => 'Це означає, що у вашому маршруті помирає ворог, який відомий MDT, однак Keystone.guru ще не прив\'язав відповідного НІПа з MDT (або ж його не існує в MDT).',
                'unable_to_find_mdt_enemy_for_kg_caused_empty_pull' => 'Цю сутичку було видалено, оскільки всі вибрані вороги відсутні в MDT, що призвело до порожньої сутички.',
                'route_title_contains_non_ascii_char_bug'           => 'Назва вашого маршруту містить символи, що не належать до ASCII, які викликають поки що нерозв\'язану помилку кодування в Keystone.guru. З назви було видалено всі такі символи. Перепрошуємо за незручність, ми сподіваємося розв\'язати цю проблему якнайшвидше.',
                'route_title_contains_non_ascii_char_bug_details'   => 'Стара назва: %s. Нова назва: %s.',
                'map_icon_contains_non_ascii_char_bug'              => 'Один з ваших коментарів на значку містить символи, що не належать до ASCII, які викликають поки що нерозв\'язану помилку кодування в Keystone.guru. З коментаря було видалено всі такі символи. Перепрошуємо за незручність, ми сподіваємося розв\'язати цю проблему якнайшвидше.',
                'map_icon_contains_non_ascii_char_bug_details'      => 'Старий коментар: «%s». Новий коментар: «%s».',
            ],
            'import_string' => [
                'category' => [
                    'awakened_obelisks' => 'Пробуджені обеліски',
                    'pulls'             => 'Сутички',
                    'notes'             => 'Примітки',
                    'pull'              => 'Сутичка %d',
                    'object'            => 'Об\'єкт %d',
                ],
                'object_out_of_bounds'                                 => 'Не вдалося розмістити коментар: об\'єкт коментаря «:comment» поза межами мапи.',
                'limit_reached_pulls'                                  => 'Не вдалося імпортувати маршрут: перевищено максимальну к-сть сутичок (:limit)',
                'limit_reached_brushlines'                             => 'Не вдалося імпортувати маршрут: перевищено максимальну к-сть ліній (:limit)',
                'limit_reached_paths'                                  => 'Не вдалося імпортувати маршрут: перевищено максимальну к-сть шляхів (:limit)',
                'limit_reached_notes'                                  => 'Не вдалося імпортувати маршрут: перевищено максимальну к-сть приміток (:limit)',
                'unable_to_find_floor_for_object'                      => 'Не вдалося знайти поверх Keystone.guru, що збігається з ID поверху %d в MDT.',
                'unable_to_find_floor_for_object_details'              => 'Це означає, що MDT містить поверх, якого немає в Keystone.guru.',
                'unable_to_find_mdt_enemy_for_clone_index'             => 'Не вдалося знайти ворога в MDT для індексу дублювання %s й індексу НІПа %s.',
                'unable_to_find_mdt_enemy_for_clone_index_details'     => 'Це означає, що у MDT існує ворог, який ще невідомий для Keystone.guru.',
                'unable_to_find_kg_equivalent_for_mdt_enemy'           => 'Не вдалося знайти відповідного НІПа %s в Keystone.guru для ворога %s з MDT (id: %s).',
                'unable_to_find_kg_equivalent_for_mdt_enemy_details'   => 'Це означає, що у вашому маршруті помирає ворог, який відомий Keystone.guru, однак Keystone.guru ще не додав на мапу.',
                'unable_to_find_awakened_enemy_for_final_boss'         => 'Не вдалося знайти ворога з ефектом Awakened %s (%s) біля останнього боса в %s.',
                'unable_to_find_awakened_enemy_for_final_boss_details' => 'Це означає, що Keystone.guru має помилку мапи, яку потрібно виправити. Повідомте мене про цю помилку, щоб я її виправив.',
                'unable_to_find_enemies_pull_skipped'                  => 'Неможливість знайти ворогів призвела до пропускання сутички.',
                'unable_to_find_enemies_pull_skipped_details'          => 'Це означає, що MDT недавно отримало оновлення, яке ще не внесли на Keystone.guru.',
                'unable_to_find_awakened_obelisks'                     => 'Не вдалося знайти Awakened Obelisks для вашої комбінації тижня й підземелля. Ваші обходи Awakened Obelisks не будуть імпортовані.',
                'unable_to_find_awakened_obelisk_different_floor'      => 'Не вдалося імпортувати Awakened Obelisk :name. Об\'єкт розташований на іншому поверсі, ніж сам обеліск. Наразі Keystone.guru не підтримує цю функцію.',
                'unable_to_decode_mdt_import_string'                   => 'Не вдалося розшифрувати імпортований рядок з MDT',
                'unable_to_validate_mdt_import_string'                 => 'Не вдалося підтвердити дійсність імпортованого рядка з MDT',
            ],
        ],
    ],
    'npcservice' => [
        'all_dungeons' => 'Усі підземелля',
    ],
    'combatlogservice' => [
        'analyze_combat_log' => [
            'verify_error'     => 'Помилка перевіряння журналу бою.',
            'processing_error' => 'Помилка опрацювання журналу бою.',
        ],
    ],

];
