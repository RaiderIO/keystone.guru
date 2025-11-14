<?php

return [

    'admintools' => [
        'error' => [
            'mdt_string_parsing_failed'           => 'Не вдалося проаналізувати рядок MDT. Ви справді вставили рядок з MDT?',
            'mdt_string_format_not_recognized'    => 'Не розпізнано формат рядка MDT.',
            'cli_weakauras_parser_not_found'      => 'Не встановлено cli_weakauras_parser.',
            'invalid_mdt_string'                  => 'Неправильний рядок MDT',
            'invalid_mdt_string_exception'        => 'Неправильний рядок MDT: %s',
            'mdt_importer_not_configured'         => 'Імпортер MDT налаштований неправильно. Повідомте адміністратора про помилку.',
            'mdt_unable_to_find_npc_for_id'       => 'Не вдалося знайти НІПа за ID %d',
            'mdt_mismatched_health'               => 'НІП %s має невідповідні значення здоров\'я (MDT: %s, KG: %s)',
            'mdt_mismatched_enemy_forces'         => 'НІП %s має невідповідні значення очок ворожих військ (MDT: %s, KG: %s)',
            'mdt_mismatched_enemy_forces_teeming' => 'НІП %s має невідповідні значення очок ворожих військ для Teeming (MDT: %s, KG: %s)',
            'mdt_mismatched_enemy_count'          => 'НІП %s має невідповідні значення кількості ворогів (MDT: %s, KG: %s)',
            'mdt_mismatched_enemy_type'           => 'НІП %s має невідповідні типи ворога (MDT: %s, KG: %s)',
            'mdt_invalid_category'                => 'Неправильна категорія',
        ],
        'flash' => [
            'message_banner_set_successfully' => 'Банер повідомлення успішно встановлено',
            'thumbnail_regenerate_result'     => 'Надіслано :success завдань для :total маршруту(-ів). Невдалих — :failed.',
            'caches_dropped_successfully'     => 'Кеш успішно очищено',
            'releases_exported'               => 'Версії експортовано',
            'exception'                       => ' У панелі адміністратора сталася халепа',
            'feature_toggle_activated'        => 'Функція :feature тепер увімкнена',
            'feature_toggle_deactivated'      => 'Функція :feature тепер вимкнена',
            'feature_forgotten'               => 'Функція :feature успішно забута',
            'read_only_mode_disabled'         => 'Режим для читання вимкнено',
            'read_only_mode_enabled'          => 'Режим для читання ввімкнено',
        ],
    ],
    'apidungeonroute' => [
        'mdt_generate_error'  => 'Під час генерування вашого рядка MDT сталася помилка: %s',
        'mdt_generate_no_lua' => 'Імпортер MDT налаштований неправильно. Повідомте адміністратора про помилку',
    ],
    'apiuserreport' => [
        'error' => [
            'unable_to_update_user_report' => 'Не вдалося оновити користувацький звіт',
            'unable_to_save_report'        => 'Не вдалося зберегти звіт',
        ],
    ],
    'brushline' => [
        'error' => [
            'unable_to_save_brushline'   => 'Не вдалося зберегти лінію',
            'unable_to_delete_brushline' => 'Не вдалося видалити лінію',
        ],
    ],
    'dungeon' => [
        'flash' => [
            'dungeon_created' => 'Підземелля створено',
            'dungeon_updated' => 'Підземелля оновлено',
        ],
    ],
    'dungeonroute' => [
        'unable_to_save' => 'Не вдалося зберегти маршрут',
        'flash'          => [
            'route_cloned_successfully' => 'Маршрут успішно продубльовано',
            'route_updated'             => 'Маршрут оновлено',
            'route_created'             => 'Маршрут створено',
        ],
    ],
    'dungeonroutediscover' => [
        'popular'           => 'Поширені маршрути',
        'this_week_affixes' => 'Модифікатори поточного тижня',
        'next_week_affixes' => 'Модифікатори наступного тижня',
        'new'               => 'Нове',
        'season'            => [
            'popular'           => 'Поширені маршрути %s',
            'this_week_affixes' => '%s поточного тижня',
            'next_week_affixes' => '%s наступного тижня',
            'new'               => 'Нові маршрути %s',
        ],
        'dungeon' => [
            'popular'           => 'Поширені маршрути %s',
            'this_week_affixes' => '%s поточного тижня',
            'next_week_affixes' => '%s наступного тижня',
            'new'               => 'Нові маршрути %s',
        ],
    ],
    'dungeonspeedrunrequirednpcs' => [
        'no_linked_npc' => 'Немає прив\'язаних НІПів',
        'flash'         => [
            'npc_added_successfully'   => 'НІПа успішно додано',
            'npc_deleted_successfully' => 'НІПа успішно прибрано',
        ],
    ],
    'expansion' => [
        'flash' => [
            'unable_to_save_expansion' => 'Не вдалося зберегти розширення',
            'expansion_updated'        => 'Розширення оновлено',
            'expansion_created'        => 'Розширення створено',
        ],
    ],
    'generic' => [
        'error' => [
            'floor_not_found_in_dungeon' => 'Поверх не належить до підземелля',
            'not_found'                  => 'Не знайдено',
        ],
    ],
    'oauthlogin' => [
        'flash' => [
            'registered_successfully' => 'Реєстрація успішна. Приємного користування сайтом!',
            'user_exists'             => 'Користувач з іменем %s уже існує. Ви вже реєструвалися?',
            'email_exists'            => 'Користувач з електронною адресою %s уже існує. Ви вже реєструвалися?',
            'permission_denied'       => 'Не вдалося зареєструватися: запит відхилено. Спробуйте ще раз.',
            'read_only_mode_enabled'  => 'Увімкнено режим для читання. Наразі ви не можете зареєструватися.',
        ],
    ],
    'register' => [
        'flash' => [
            'registered_successfully' => 'Реєстрація успішна. Приємного користування сайтом!',
        ],
        'legal_agreed_required' => 'Для реєстрації необхідно погодитися з нашими умовами користування.',
        'legal_agreed_accepted' => 'Для реєстрації необхідно погодитися з нашими умовами користування.',
    ],
    'release' => [
        'error' => [
            'unable_to_save_release' => 'Не вдалося зберегти версію',
        ],
        'flash' => [
            'release_updated'  => 'Версію оновлено',
            'release_created'  => 'Версію створено',
            'github_exception' => 'Під час зв\'язку з Github сталася помилка: :message',
        ],
    ],
    'mappingversion' => [
        'created_successfully'      => 'Додано нову версію мапи!',
        'created_bare_successfully' => 'Додано нову чисту версію мапи!',
        'deleted_successfully'      => 'Версію мапи успішно видалено',
    ],
    'mdtimport' => [
        'unknown_dungeon' => 'Невідоме підземелля',
        'error'           => [
            'mdt_string_parsing_failed'             => 'Не вдалося проаналізувати рядок MDT. Ви справді вставили рядок з MDT?',
            'mdt_string_format_not_recognized'      => 'Не розпізнано формат рядка MDT.',
            'cli_weakauras_parser_not_found'        => 'Не встановлено cli_weakauras_parser.',
            'invalid_mdt_string_exception'          => 'Неправильний рядок MDT: %s',
            'invalid_mdt_string'                    => 'Неправильний рядок MDT',
            'mdt_importer_not_configured_properly'  => 'Імпортер MDT налаштований неправильно. Повідомте адміністратора про помилку.',
            'cannot_create_route_must_be_logged_in' => 'Потрібно ввійти в систему, щоб створити маршрут',
        ],
    ],
    'path' => [
        'error' => [
            'unable_to_save_path'   => 'Не вдалося зберегти шлях',
            'unable_to_delete_path' => 'Не вдалося видалити шлях',
        ],
    ],
    'patreon' => [
        'flash' => [
            'unlink_successful'       => 'Ваш обліковий запис Patreon успішно від\'єднано.',
            'link_successful'         => 'Ваш обліковий запис Patreon успішно приєднано. Дякуємо!',
            'patreon_session_expired' => 'Час вашого сеансу на Patreon сплинув. Спробуйте знову.',
            'session_expired'         => 'Час вашого сеансу сплинув. Спробуйте знову.',
            'patreon_error_occurred'  => 'Виникла помилка на боці Patreon. Спробуйте ще раз пізніше.',
            'internal_error_occurred' => 'Під час оброблення відповіді Patreon сталася помилка. Видається, що вона має неправильний формат. Помилка записана й буде усунена. Спробуйте ще раз пізніше.',
        ],
    ],
    'profile' => [
        'flash' => [
            'email_already_in_use'             => 'Це ім\'я вже використовується.',
            'username_already_in_use'          => 'Це ім\'я вже використовується.',
            'profile_updated'                  => 'Профіль оновлено',
            'unexpected_error_when_saving'     => 'Під час зберігання вашого профілю виникла несподівана помилка',
            'privacy_settings_updated'         => 'Налаштування приватності оновлено',
            'password_changed'                 => 'Пароль змінено',
            'new_password_equals_old_password' => 'Новий пароль збігається зі старим паролем',
            'new_passwords_do_not_match'       => 'Нові паролі не збігаються',
            'current_password_is_incorrect'    => 'Поточний пароль неправильний',
            'tag_created_successfully'         => 'Мітку успішно створено',
            'tag_already_exists'               => 'Ця мітка вже існує',
            'admins_cannot_delete_themselves'  => 'Адміністратори не можуть видаляти самих себе!',
            'account_deleted_successfully'     => 'Обліковий запис успішно видалено.',
            'error_deleting_account'           => 'Виникла помилка. Спробуйте знову.',
        ],
    ],
    'spell' => [
        'error' => [
            'unable_to_save_spell' => 'Не вдалося зберегти закляття',
        ],
        'flash' => [
            'spell_updated' => 'Закляття оновлено',
            'spell_created' => 'Закляття створено',
        ],
    ],
    'team' => [
        'flash' => [
            'team_updated'                        => 'Команду оновлено',
            'team_created'                        => 'Команду створено',
            'unable_to_find_team_for_invite_code' => 'Не вдалося знайти команду, пов\'язану з цим кодом запрошення',
            'invite_accept_success'               => 'Успіх! Ви доєдналися до команди %s.',
            'tag_created_successfully'            => 'Мітку успішно створено',
            'tag_already_exists'                  => 'Ця мітка вже існує',
        ],
    ],
    'user' => [
        'flash' => [
            'user_is_now_an_admin'              => ':user тепер має права адміністратора',
            'user_is_no_longer_an_admin'        => ':user більше не має прав адміністратора',
            'user_is_now_a_user'                => ':user тепер має права користувача',
            'user_is_now_a_role'                => ':user тепер має роль «:role»',
            'account_deleted_successfully'      => 'Обліковий запис успішно видалено.',
            'account_deletion_error'            => 'Виникла помилка. Спробуйте знову.',
            'user_is_not_a_patron'              => 'Цей користувач не має підписки на Patreon.',
            'all_benefits_granted_successfully' => 'Успішно надано всі бонуси.',
            'error_granting_all_benefits'       => 'Під час надавання всіх бонусів виникла помилка.',
        ],
    ],

];
