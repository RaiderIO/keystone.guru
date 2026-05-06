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
                'unable_to_find_mdt_enemy_for_kg_enemy'             => 'Не вдалося знайти відповідник у MDT до ворога з НІПом %s на Keystone.guru (enemy_id: %d, npc_id: %d).',
                'unable_to_find_mdt_enemy_for_kg_enemy_details'     => 'У вашому маршруті помирає ворог, чий НІП відомий MDT, але якого Keystone.guru ще не прив\'язав до відповідника в MDT (або ж його не існує в MDT).',
                'unable_to_find_mdt_enemy_for_kg_caused_empty_pull' => 'Цю сутичку було видалено, оскільки не вдалося знайти всіх вибраних ворогів у MDT, що призвело до порожньої сутички.',
                'route_title_contains_non_ascii_char_bug'           => 'Назва вашого маршруту містить символи, що не належать до ASCII й спричиняють досі невиправлену помилку кодування в Keystone.guru. Усі проблемні символи було видалено з назви. Перепрошуємо за незручність, ми сподіваємося розв\'язати цю проблему якнайшвидше.',
                'route_title_contains_non_ascii_char_bug_details'   => 'Стара назва: %s. Нова назва: %s.',
                'map_icon_contains_non_ascii_char_bug'              => 'Один з ваших коментарів на значку містить символи, що не належать до ASCII й спричиняють досі невиправлену помилку кодування в Keystone.guru. Усі проблемні символи було видалено з коментаря. Перепрошуємо за незручність, ми сподіваємося розв\'язати цю проблему якнайшвидше.',
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
                'object_out_of_bounds'                                 => 'Не вдалося розмістити коментар: об\'єкт коментаря «:comment» перебуває поза межами мапи.',
                'limit_reached_pulls'                                  => 'Не вдалося імпортувати маршрут: перевищено максимальну к-сть сутичок (:limit)',
                'limit_reached_brushlines'                             => 'Не вдалося імпортувати маршрут: перевищено максимальну к-сть ліній (:limit)',
                'limit_reached_paths'                                  => 'Не вдалося імпортувати маршрут: перевищено максимальну к-сть шляхів (:limit)',
                'limit_reached_notes'                                  => 'Не вдалося імпортувати маршрут: перевищено максимальну к-сть приміток (:limit)',
                'unable_to_find_floor_for_object'                      => 'Не вдалося знайти поверх на Keystone.guru, що збігається з ID поверху %d в MDT.',
                'unable_to_find_floor_for_object_details'              => 'MDT містить поверх, якого немає в Keystone.guru.',
                'unable_to_find_mdt_enemy_for_clone_index'             => 'Не вдалося знайти ворога в MDT для індексу дублювання %s й індексу НІПа %s.',
                'unable_to_find_mdt_enemy_for_clone_index_details'     => 'На мапах MDT існує ворог, якого Keystone.guru ще не знає.',
                'unable_to_find_kg_equivalent_for_mdt_enemy'           => 'Не вдалося знайти відповідник на Keystone.guru до ворога %s з НІПом %s у MDT (id: %s).',
                'unable_to_find_kg_equivalent_for_mdt_enemy_details'   => 'У вашому маршруті помирає ворог, чий НІП відомий Keystone.guru, але якого Keystone.guru ще не додав на мапу.',
                'unable_to_find_awakened_enemy_for_final_boss'         => 'Не вдалося знайти ворога з ефектом Awakened %s (%s) біля останнього боса в %s.',
                'unable_to_find_awakened_enemy_for_final_boss_details' => 'Keystone.guru має помилку мапи, яку доведеться виправити. Надішліть мені вищезазначене зауваження, щоб я її виправив.',
                'unable_to_find_enemies_pull_skipped'                  => 'Неможливість знайти ворогів призвела до пропуску сутички.',
                'unable_to_find_enemies_pull_skipped_details'          => 'Це може означати, що MDT недавно отримав оновлення, яке ще не внесли на Keystone.guru.',
                'unable_to_find_awakened_obelisks'                     => 'Не вдалося знайти пробуджені обеліски (Awakened Obelisks) для вашої комбінації тижня й підземелля. Пов\'язані з ними обходи не будуть імпортовані.',
                'unable_to_find_awakened_obelisk_different_floor'      => 'Не вдалося імпортувати пробуджений обеліск :name. Об\'єкт розташований на іншому поверсі, ніж сам обеліск. Наразі Keystone.guru не підтримує цю функцію.',
                'unable_to_decode_mdt_import_string'                   => 'Не вдалося розшифрувати імпортований рядок з MDT',
                'unable_to_validate_mdt_import_string'                 => 'Не вдалося перевірити імпортований рядок з MDT',
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
