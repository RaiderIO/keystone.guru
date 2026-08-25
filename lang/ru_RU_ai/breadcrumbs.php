<?php

return [

    'home' => [
        'front_page' => 'Keystone.guru',
        'compendium' => [
            'npc'          => 'Компендиум НПЦ',
            'npc_show'     => ':name',
            'spell'        => 'Компендиум заклинаний',
            'spell_show'   => ':name',
            'activity'     => 'Активность компендиума',
            'activity_day' => ':date',
            'tuning'       => '',
            'class'        => 'По классу',
        ],
        'affixes' => 'Аффиксы',
        'about'   => 'О нас',
        'credits' => 'Благодарности',
        'legal'   => [
            'cookies' => 'Куки',
            'privacy' => 'Конфиденциальность',
            'terms'   => 'Условия',
        ],
        'routes'              => 'Маршруты',
        'routes_expansion'    => ':expansion маршруты',
        'routes_game_version' => ':gameVersion маршруты',
        'gameversion'         => [
            'update'  => ':gameVersion',
            'dungeon' => [
                'heatmap' => 'Тепловая карта',
                'explore' => 'Исследовать',
            ],
        ],
        'dungeonroute' => [
            'new' => 'Новый маршрут',
        ],
        'dungeonroutes' => [
            'search'        => 'Поиск',
            'popular'       => 'Популярные',
            'new'           => 'Новые',
            'routes_season' => 'Сезон :season',
            'season'        => [
                'popular' => 'Популярные',
                'new'     => 'Новый',
            ],
            'discoverdungeon' => [
                'popular' => 'Популярные',
                'new'     => 'Новые',
            ],
        ],
        'my_favorites'     => 'Мои избранные',
        'account_settings' => 'Настройки аккаунта',
        'my_routes'        => 'Мои маршруты',
        'my_tags'          => 'Мои теги',
        'my_teams'         => 'Моя команда',
        'overview'         => 'Обзор',
        'new_team'         => 'Новая команда',
        'edit_team'        => 'Редактировать команду',
        'join_team'        => 'Присоединиться к команде',
        'admin'            => [
            'admin'   => 'Администратор',
            'affixes' => [
                'affixes'    => 'Аффиксы',
                'new_affix'  => 'Новый аффикс',
                'edit_affix' => 'Редактировать :affix',
            ],
            'seasons' => [
                'seasons'     => 'Сезоны',
                'new_season'  => 'Новый сезон',
                'edit_season' => 'Редактировать :season',
            ],
            'affixgroups' => [
                'new_affixgroup'  => 'Новая группа аффиксов',
                'edit_affixgroup' => 'Редактировать группу аффиксов',
            ],
            'tools' => [
                'admin_tools'                                 => 'Инструменты администратора',
                'view_exported_dungeondata'                   => 'Просмотреть экспортированные данные подземелий',
                'select_exception'                            => 'Выбрать исключения',
                'mdt_diff'                                    => 'Отличия MDT',
                'view_mdt_string_contents'                    => 'Просмотр содержимого строк MDT',
                'import_npcs'                                 => 'Импортировать НПЦ',
                'spells_missing_info'                         => 'Заклинания с отсутствующей информацией',
                'npcs_missing_display_id'                     => 'НПЦ с отсутствующим ID отображения',
                'thumbnails_regenerate'                       => 'Перегенерировать эскизы',
                'combat_log_regenerate'                       => 'Перегенерировать маршруты ARC',
                'combat_log_criteria'                         => 'Критерии парсинга компендиума НПЦ',
                'combat_log_run_data'                         => 'Очистка данных прохождений боевого журнала',
                'combat_log_route_coverage'                   => 'Покрытие сил врага ARC',
                'dungeonroute_view'                           => 'Просмотр маршрута подземелья',
                'dungeonroute_view_contents'                  => 'Содержимое маршрута',
                'dungeonroute_mapping_version_usage'          => 'Использование версии карты',
                'enemyforces_import'                          => 'Импорт сил врага',
                'enemyforces_recalculate'                     => 'Пересчет сил врага',
                'features_list'                               => 'Функции',
                'mdt_dungeon_mapping_hash'                    => 'Хеш карты подземелья',
                'mdt_dungeon_mapping_version_accuracy'        => 'Точность версии карты подземелья',
                'mdt_dungeon_mapping_version_to_mdt_mapping'  => 'Соответствие версии карты подземелья MDT-карте',
                'mdt_dungeonroute'                            => 'MDT-маршрут подземелья',
                'mdt_list'                                    => 'Список MDT-строк',
                'messagebanner_set'                           => 'Установить баннер сообщения',
                'npc_manage_spell_visibility'                 => 'Управление видимостью заклинаний НПЦ',
                'wagogg_import_ingame_coordinates'            => 'Импорт игровых координат',
                'artisancommands_backfill_kill_zone_enemy_id' => 'Заполнение ID врагов зон убийства',
            ],
            'expansions' => [
                'expansions'     => 'Дополнение',
                'new_expansion'  => 'Новое дополнение',
                'edit_expansion' => 'Редактировать дополнение',
            ],
            'dungeons' => [
                'dungeons'     => 'Подземелье',
                'new_dungeon'  => 'Новое подземелье',
                'edit_dungeon' => 'редактировать подземелье',
            ],
            'floors' => [
                'new_floor'  => 'Этаж',
                'edit_floor' => 'редактировать этаж',
            ],
            'dungeonspeedrunrequirednpc' => [
                'new_dungeonspeedrunrequirednpc' => 'Новый обязательный НПЦ для спидрана подземелья (:difficulty)',
            ],
            'npcs' => [
                'npcs'     => 'НПЦ',
                'new_npc'  => 'Новый НПЦ',
                'edit_npc' => 'Редактировать НПЦ',
            ],
            'npcenemyforces' => [
                'new_npc_enemy_forces'  => 'Новые силы врагов НПЦ',
                'edit_npc_enemy_forces' => 'Редактировать силы врагов НПЦ',
            ],
            'npchealth' => [
                'new_npc_health'  => 'Новое здоровье НПЦ',
                'edit_npc_health' => 'Редактировать здоровье НПЦ',
            ],
            'spells' => [
                'spells'     => 'Способность',
                'new_spell'  => 'Новая способность',
                'edit_spell' => 'Редактировать способность',
            ],
            'users' => [
                'users' => 'Пользователь',
            ],
            'user_reports' => [
                'user_reports' => 'Отчеты пользователей',
            ],
        ],
    ],

];
