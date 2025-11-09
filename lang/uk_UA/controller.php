<?php

return [

    'admintools' => [
        'error' => [
            'mdt_string_parsing_failed'           => 'Не вдалося проаналізувати рядок MDT. Ви справді вставили рядок з MDT?',
            'mdt_string_format_not_recognized'    => 'Не розпізнано формат рядка MDT.',
            'cli_weakauras_parser_not_found'      => 'Не встановлено cli_weakauras_parser.',
            'invalid_mdt_string'                  => 'Недійсний рядок MDT',
            'invalid_mdt_string_exception'        => 'Недійсний рядок MDT: %s',
            'mdt_importer_not_configured'         => 'Імпортер MDT налаштований неправильно. Будь ласка, повідомте адміністратора про цю помилку.',
            'mdt_unable_to_find_npc_for_id'       => 'Неможливо знайти НІПа за ID %d',
            'mdt_mismatched_health'               => 'НІП %s має різні значення здоров\'я (MDT: %s, KG: %s)',
            'mdt_mismatched_enemy_forces'         => 'НІП %s має різні значення очок ворожих військ (MDT: %s, KG: %s)',
            'mdt_mismatched_enemy_forces_teeming' => 'НІП %s має різні значення очок ворожих військ з модифікатором Teeming (MDT: %s, KG: %s)',
            'mdt_mismatched_enemy_count'          => 'НІП %s має різну кількість ворогів (MDT: %s, KG: %s)',
            'mdt_mismatched_enemy_type'           => 'НІП %s має різні типи ворога (MDT: %s, KG: %s)',
            'mdt_invalid_category'                => 'Недійсна категорія',
        ],
        'flash' => [
            'message_banner_set_successfully' => 'Банер повідомлення успішно встановлено',
            'thumbnail_regenerate_result'     => 'Виконано завдання для :total маршруту(-ів). Успішних — :success, невдалих — :failed.',
            'caches_dropped_successfully'     => 'Кеш успішно очищено',
            'releases_exported'               => 'Експортовано релізи',
            'exception'                       => 'Виникла помилка в панелі адміністратора',
            'feature_toggle_activated'        => 'Функція :feature тепер увімкнена',
            'feature_toggle_deactivated'      => 'Функція :feature тепер вимкнена',
            'feature_forgotten'               => 'Функція :feature успішно забута',
            'read_only_mode_disabled'         => 'Режим для читання вимкнено',
            'read_only_mode_enabled'          => 'Режим для читання ввімкнено',
        ],
    ],
    'apidungeonroute' => [
        'mdt_generate_error'  => 'Виникла помилка під час генерування вашого рядка MDT: %s',
        'mdt_generate_no_lua' => 'Імпортер MDT налаштований неправильно. Будь ласка, повідомте адміністратора про цю помилку',
    ],
    'apiuserreport' => [
        'error' => [
            'unable_to_update_user_report' => 'Неможливо оновити користувацький звіт',
            'unable_to_save_report'        => 'Неможливо зберегти звіт',
        ],
    ],
    'brushline' => [
        'error' => [
            'unable_to_save_brushline'   => 'Неможливо зберегти лінію',
            'unable_to_delete_brushline' => 'Неможливо видалити лінію',
        ],
    ],
    'dungeon' => [
        'flash' => [
            'dungeon_created' => 'Підземелля створено',
            'dungeon_updated' => 'Підземелля оновлено',
        ],
    ],
    'dungeonroute' => [
        'unable_to_save' => 'Неможливо зберегти маршрут',
        'flash'          => [
            'route_cloned_successfully' => 'Маршрут успішно продубльовано',
            'route_updated'             => 'Маршрут оновлено',
            'route_created'             => 'Маршрут створено',
        ],
    ],
    'dungeonroutediscover' => [
        'popular'           => 'Поширені підземелля',
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
            'npc_added_successfully'   => 'НІПів успішно додано',
            'npc_deleted_successfully' => 'НІПів успішно прибрано',
        ],
    ],
    'expansion' => [
        'flash' => [
            'unable_to_save_expansion' => 'Неможливо зберегти доповнення',
            'expansion_updated'        => 'Доповнення оновлено',
            'expansion_created'        => 'Доповнення створено',
        ],
    ],
    'generic' => [
        'error' => [
            'floor_not_found_in_dungeon' => 'Поверх не належить підземеллю',
            'not_found'                  => 'Не знайдено',
        ],
    ],
    'oauthlogin' => [
        'flash' => [
            'registered_successfully' => 'Успішно зареєстровано. Насолоджуйтеся вебсайтом!',
            'user_exists'             => 'Уже існує користувач з іменем %s. Ви вже реєструвалися раніше?',
            'email_exists'            => 'Уже існує користувач з електронною адресою %s. Ви вже реєструвалися раніше?',
            'permission_denied'       => 'Неможливо зареєструватися. Запит було відхилено. Спробуйте знову.',
            'read_only_mode_enabled'  => 'Увімкнено режим для читання. Наразі ви не можете зареєструватися.',
        ],
    ],
    'register' => [
        'flash' => [
            'registered_successfully' => 'Успішно зареєстровано. Насолоджуйтеся вебсайтом!',
        ],
        'legal_agreed_required' => 'Для реєстрації необхідно погодитися з нашими юридичними умовами.',
        'legal_agreed_accepted' => 'Для реєстрації необхідно погодитися з нашими юридичними умовами.',
    ],
    'release' => [
        'error' => [
            'unable_to_save_release' => 'Неможливо зберегти реліз',
        ],
        'flash' => [
            'release_updated'  => 'Реліз оновлено',
            'release_created'  => 'Реліз створено',
            'github_exception' => 'Виникла помилка під час зв\'язку з Github: :message',
        ],
    ],
    'mappingversion' => [
        'created_successfully'      => 'Додано нову версію складання мапи!',
        'created_bare_successfully' => 'Додано нову чисту версію складання мапи!',
        'deleted_successfully'      => 'Успішно видалено версію складання мапи',
    ],
    'mdtimport' => [
        'unknown_dungeon' => 'Невідоме підземелля',
        'error'           => [
            'mdt_string_parsing_failed'             => 'Не вдалося проаналізувати рядок MDT. Ви справді вставили рядок з MDT?',
            'mdt_string_format_not_recognized'      => 'Не розпізнано формат рядка MDT.',
            'cli_weakauras_parser_not_found'        => 'Не встановлено cli_weakauras_parser.',
            'invalid_mdt_string_exception'          => 'Недійсний рядок MDT: %s',
            'invalid_mdt_string'                    => 'Недійсний рядок MDT',
            'mdt_importer_not_configured_properly'  => 'Імпортер MDT налаштований неправильно. Будь ласка, повідомте адміністратора про цю помилку.',
            'cannot_create_route_must_be_logged_in' => 'Потрібно ввійти в систему, щоб створювати маршрути',
        ],
    ],
    'path' => [
        'error' => [
            'unable_to_save_path'   => 'Неможливо зберегти шлях',
            'unable_to_delete_path' => 'Неможливо видалити шлях',
        ],
    ],
    'patreon' => [
        'flash' => [
            'unlink_successful'       => 'Ваш обліковий запис Patreon успішно від\'єднано.',
            'link_successful'         => 'Ваш обліковий запис Patreon успішно приєднано. Дякуємо!',
            'patreon_session_expired' => 'Час вашого сеансу на Patreon сплинув. Спробуйте знову.',
            'session_expired'         => 'Час вашого сеансу сплинув. Спробуйте знову.',
            'patreon_error_occurred'  => 'Виникла помилка на боці Patreon. Спробуйте ще раз пізніше.',
            'internal_error_occurred' => 'Виникла помилка під час обробляння відповіді Patreon. Видається, що вона має неправильний формат. Помилка записана й буде усунена. Спробуйте ще раз пізніше.',
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
            'admins_cannot_delete_themselves'  => 'Адміністратори не можуть себе видаляти!',
            'account_deleted_successfully'     => 'Обліковий запис успішно видалено.',
            'error_deleting_account'           => 'Виникла помилка. Будь ласка, спробуйте знову.',
        ],
    ],
    'spell' => [
        'error' => [
            'unable_to_save_spell' => 'Неможливо зберегти закляття',
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
            'account_deletion_error'            => 'Виникла помилка. Будь ласка, спробуйте знову.',
            'user_is_not_a_patron'              => 'Цей користувач не має підписки на Patreon.',
            'all_benefits_granted_successfully' => 'Успішно надано всі переваги.',
            'error_granting_all_benefits'       => 'Виникла помилка під час надавання всіх переваг.',
        ],
    ],

];
