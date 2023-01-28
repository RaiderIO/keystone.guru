<?php


return [
    'admintools'                  => [
        'error' => [
            'mdt_string_format_not_recognized'    => 'Формат строки MDT не распознан.',
            'invalid_mdt_string'                  => 'Неверная строка MDT',
            'invalid_mdt_string_exception'        => 'Неверная строка MDT: %s',
            'mdt_importer_not_configured'         => 'Импорт MDT настроен неправильно. Пожалуйста, свяжитесь с администратором по поводу этой проблемы.',
            'mdt_unable_to_find_npc_for_id'       => 'Невозможно найти NPC по ID %d',
            'mdt_mismatched_health'               => 'Не совпадает здоровье NPC %s, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces'         => 'Не соответствующая способность NPC %s, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces_teeming' => 'Таймеры NPC %s не соответствуют, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_count'          => 'Несоответствующее количество NPC %s, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_type'           => 'Несоответствующее тип NPC %s, MDT: %s, KG: %s',
            'mdt_invalid_category'                => 'Неверная категория',
        ],
        'flash' => [
            'caches_dropped_successfully' => 'Кеш сброшен успешно',
            'releases_exported'           => 'Релизы экспортированы',
            'exception'                   => [
                'token_mismatch'        => 'Несоответствие токена',
                'internal_server_error' => 'Дополнение добавлено в админ панель',
            ],
        ],
    ],
    'apidungeonroute'             => [
        'mdt_generate_error'  => 'Произошла ошибка при создании строки MDT: %s',
        'mdt_generate_no_lua' => 'Импорт MDT настроен неправильно. Пожалуйста, свяжитесь с администратором по поводу этой проблемы',
    ],
    'apiuserreport'               => [
        'error' => [
            'unable_to_update_user_report' => 'Невозможно обновить отчет пользователя',
            'unable_to_save_report'        => 'Невозможно сохранить отчет',
        ],
    ],
    'dungeon'                     => [
        'flash' => [
            'dungeon_created' => 'Подземелье создано',
            'dungeon_updated' => 'Подземелье обновлено',
        ],
    ],
    'dungeonroute'                => [
        'unable_to_save' => 'Невозможно сохранить маршрут',
        'flash'          => [
            'route_cloned_successfully' => 'Маршрут успешно клонирован',
            'route_updated'             => 'Маршрут обновлен',
            'route_created'             => 'Маршрут создан',
        ],
    ],
    'dungeonroutediscover'        => [
        'popular'           => 'Популярные маршруты',
        'this_week_affixes' => 'Текущие аффиксы',
        'next_week_affixes' => 'Аффиксы следующей неделе',
        'new'               => 'Новые',
        'dungeon'           => [
            'popular'           => '%s популярные маршруты',
            'this_week_affixes' => '%s текущие аффиксы',
            'next_week_affixes' => '%s аффиксы следующей неделе',
            'new'               => '%s Новые',
        ],
    ],
    'dungeonspeedrunrequirednpcs' => [
        'no_linked_npc' => '@todo ru: .dungeonspeedrunrequirednpcs.no_linked_npc',
        'flash'         => [
            'npc_added_successfully'   => '@todo ru: .dungeonspeedrunrequirednpcs.flash.npc_added_successfully',
            'npc_deleted_successfully' => '@todo ru: .dungeonspeedrunrequirednpcs.flash.npc_deleted_successfully',
        ],
    ],
    'expansion'                   => [
        'flash' => [
            'unable_to_save_expansion' => 'Не удалось сохранить дополнение',
            'expansion_updated'        => 'Дополнение обновлено',
            'expansion_created'        => 'Дополнение создано',
        ],
    ],
    'oauthlogin'                  => [
        'flash' => [
            'registered_successfully' => 'Регистрация прошла успешно.',
            'user_exists'             => 'Пользователь с таким именем уже существует %s. Может быть вы уже зарегистрированы?',
            'email_exists'            => 'Пользователь с таким электронным адресом уже существует %s. Может быть вы уже зарегистрированы?',
        ],
    ],
    'register'                    => [
        'flash'                 => [
            'registered_successfully' => 'Регистрация прошла успешно.',
        ],
        'legal_agreed_required' => 'Вы должны согласиться с пользовательским соглашением и политикой конфиденциальности для регистрации',
        'legal_agreed_accepted' => 'Вы должны согласиться с пользовательским соглашением и политикой конфиденциальности для регистрации',
    ],
    'release'                     => [
        'error' => [
            'unable_to_save_release' => 'Невозможно сохранить релиз',
        ],
        'flash' => [
            'release_updated'  => 'Релиз обновлен',
            'release_created'  => 'Релиз создан',
            'github_exception' => 'Произошла ошибка связи с Github: %s',
        ],
    ],
    'mappingversion'              => [
        'created_successfully' => '@todo ru: .mappingversion.created_successfully',
        'deleted_successfully' => '@todo ru: .mappingversion.deleted_successfully',
    ],
    'mdtimport'                   => [
        'unknown_dungeon' => 'Неизвестное подземелье',
        'error'           => [
            'mdt_string_format_not_recognized'      => 'Формат строки MDT не распознан.',
            'invalid_mdt_string_exception'          => 'Недействительная строка MDT: %s',
            'invalid_mdt_string'                    => 'Недействительная строка MDT',
            'mdt_importer_not_configured_properly'  => 'Импорт MDT настроен неправильно. Пожалуйста, свяжитесь с администратором по поводу этой проблемы.',
            'cannot_create_route_must_be_logged_in' => 'Вы должны авторизоваться, чтобы создать маршрут',
        ],
    ],
    'patreon'                     => [
        'flash' => [
            'unlink_successful'       => '@todo ru: .patreon.flash.unlink_successful',
            'link_successful'         => '@todo ru: .patreon.flash.link_successful',
            'patreon_session_expired' => '@todo ru: .patreon.flash.patreon_session_expired',
            'session_expired'         => '@todo ru: .patreon.flash.session_expired',
            'patreon_error_occurred'  => '@todo ru: .patreon.flash.patreon_error_occurred',
            'internal_error_occurred' => '@todo ru: .patreon.flash.internal_error_occurred',
        ],
    ],
    'profile'                     => [
        'flash' => [
            'email_already_in_use'             => 'Пользователь с таким электронным адресом уже существует',
            'username_already_in_use'          => 'Пользователь с таким именем уже существует',
            'profile_updated'                  => 'Профиль обновлен',
            'unexpected_error_when_saving'     => 'Произошла непредвиденная ошибка при попытке сохранить ваш профиль.',
            'privacy_settings_updated'         => 'Настройки конфиденциальности обновлены',
            'password_changed'                 => 'Пароль изменен',
            'new_password_equals_old_password' => 'Новый пароль совпадает со старым паролем',
            'new_passwords_do_not_match'       => 'Новый пароль не совпадает',
            'current_password_is_incorrect'    => 'Текущий пароль неверен',
            'tag_created_successfully'         => 'Тег создан успешно',
            'tag_already_exists'               => 'Этот тег уже существует',
            'admins_cannot_delete_themselves'  => 'Админ не может удалить сам себя!',
            'account_deleted_successfully'     => 'Аккаунт успешно удален.',
            'error_deleting_account'           => 'Произошла ошибка. Пожалуйста, попробуйте еще раз.',
        ],
    ],
    'spell'                       => [
        'error' => [
            'unable_to_save_spell' => 'Невозможно сохранить способность',
        ],
        'flash' => [
            'spell_updated' => 'Способность обновлена',
            'spell_created' => 'Способность создана',
        ],
    ],
    'team'                        => [
        'flash' => [
            'team_updated'                        => 'Команда обновлена',
            'team_created'                        => 'Команда создана',
            'unable_to_find_team_for_invite_code' => 'Невозможно найти команду, связанную с этим кодом приглашения',
            'invite_accept_success'               => 'Теперь ты член команды %s.',
            'tag_created_successfully'            => 'Тег успешно создан',
            'tag_already_exists'                  => 'Этот тег уже существует',
        ],
    ],
    'user'                        => [
        'flash' => [
            'user_is_now_an_admin'         => 'Пользователь %s теперь администратор',
            'user_is_no_longer_an_admin'   => 'Пользователь %s больше не администратор',
            'user_is_now_a_user'           => 'Пользователь %s теперь пользователь',
            'account_deleted_successfully' => 'Аккаунт успешно удален.',
            'account_deletion_error'       => 'Произошла ошибка. Пожалуйста, попробуйте еще раз.',
            'user_is_not_a_patron'         => 'Этот пользователь не подписчик Patron',
        ],
    ],
];
