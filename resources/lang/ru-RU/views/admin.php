<?php

return [
    'dungeon'                    => [
        'edit' => [
            'title_new'                       => 'Новое подземелье',
            'title_edit'                      => 'Редактировать подземелье',
            'header_new'                      => 'Новое подземелье',
            'header_edit'                     => 'Редактировать подземелье',
            'active'                          => 'Действующий',
            'speedrun_enabled'                => '@todo ru: .dungeon.edit.speedrun_enabled',
            'zone_id'                         => 'ID зоны',
            'map_id'                          => '@todo ru: .dungeon.edit.map_id',
            'mdt_id'                          => 'MDT ID',
            'dungeon_name'                    => 'Название подземелья',
            'key'                             => 'Ключ',
            'slug'                            => 'Жетон',
            'enemy_forces_required'           => 'Требуется больше сил врага',
            'enemy_forces_required_teeming'   => 'Требуется больше сил врага (Кишащий)',
            'enemy_forces_shrouded'           => '@todo ru: .dungeon.edit.enemy_forces_shrouded',
            'enemy_forces_shrouded_zul_gamux' => '@todo ru: .dungeon.edit.enemy_forces_shrouded_zul_gamux',
            'timer_max_seconds'               => 'Таймер (секунды)',
            'submit'                          => 'Подтвердить',

            'floor_management' => [
                'title'                => '@todo ru: .dungeon.edit.floor_management.title',
                'add_floor'            => '@todo ru: .dungeon.edit.floor_management.add_floor',
                'table_header_id'      => '@todo ru: .dungeon.edit.floor_management.table_header_id',
                'table_header_index'   => '@todo ru: .dungeon.edit.floor_management.table_header_index',
                'table_header_name'    => '@todo ru: .dungeon.edit.floor_management.table_header_name',
                'table_header_actions' => '@todo ru: .dungeon.edit.floor_management.table_header_actions',
                'floor_edit_edit'      => '@todo ru: .dungeon.edit.floor_management.floor_edit_edit',
                'floor_edit_mapping'   => '@todo ru: .dungeon.edit.floor_management.floor_edit_mapping',
            ],

            'mapping_versions' => [
                'title'                   => '@todo ru: .dungeon.edit.mapping_versions.title',
                'add_mapping_version'     => '@todo ru: .dungeon.edit.mapping_versions.add_mapping_version',
                'delete'                  => '@todo ru: .dungeon.edit.mapping_versions.delete',
                'table_header_merged'     => '@todo ru: .dungeon.edit.mapping_versions.table_header_merged',
                'table_header_id'         => '@todo ru: .dungeon.edit.mapping_versions.table_header_id',
                'table_header_version'    => '@todo ru: .dungeon.edit.mapping_versions.table_header_version',
                'table_header_created_at' => '@todo ru: .dungeon.edit.mapping_versions.table_header_created_at',
                'table_header_actions'    => '@todo ru: .dungeon.edit.mapping_versions.table_header_actions',
            ],
        ],
        'list' => [
            'title'                             => 'Список подземелий',
            'header'                            => 'Показать подземелья',
            'table_header_active'               => 'Действующий',
            'table_header_expansion'            => 'Опыт',
            'table_header_name'                 => 'Имя',
            'table_header_enemy_forces'         => 'Силы врага',
            'table_header_enemy_forces_teeming' => 'Кишащий СВ',
            'table_header_timer'                => 'Таймер',
            'table_header_actions'              => 'Действия',
            'edit'                              => 'Редактировать',
        ],
    ],
    'dungeonspeedrunrequirednpc' => [
        'new' => [
            'title'          => '@todo ru: .dungeonspeedrunrequirednpc.new.title',
            'header'         => '@todo ru: .dungeonspeedrunrequirednpc.new.header',
            'npc_id'         => '@todo ru: .dungeonspeedrunrequirednpc.new.npc_id',
            'linked_npc_ids' => '@todo ru: .dungeonspeedrunrequirednpc.new.linked_npc_ids',
            'count'          => '@todo ru: .dungeonspeedrunrequirednpc.new.count',
            'submit'         => '@todo ru: .dungeonspeedrunrequirednpc.new.submit',
        ],
    ],
    'expansion'                  => [
        'edit' => [
            'title_new'     => 'Новое дополнение',
            'header_new'    => 'Новое дополнение',
            'title_edit'    => 'Редактировать дополнение',
            'header_edit'   => 'Редактировать дополнение',
            'active'        => 'Действующий',
            'name'          => 'Название',
            'shortname'     => 'Короткое название',
            'icon'          => 'Иконка',
            'current_image' => 'Текущее изображение',
            'color'         => 'Цвет',
            'edit'          => 'Редактировать',
            'submit'        => 'Подтвердить',
        ],
        'list' => [
            'title'                => 'Список дополнений',
            'header'               => 'Показать дополнение',
            'create_expansion'     => 'Создать дополнение',
            'table_header_active'  => 'Активировать',
            'table_header_icon'    => 'Иконка',
            'table_header_id'      => 'ID',
            'table_header_name'    => 'Название',
            'table_header_color'   => 'Цвет',
            'table_header_actions' => 'Действия',
            'edit'                 => 'Редактировать',
        ],
    ],
    'floor'                      => [
        'flash'   => [
            'invalid_floor_id'           => 'Этаж %s не является частью подземелья %s',
            'invalid_mapping_version_id' => '@todo ru: .floor.flash.invalid_mapping_version_id',
            'floor_updated'              => 'Этаж обновлен',
            'floor_created'              => 'Этаж создан',
        ],
        'edit'    => [
            'title_new'               => 'Новый этаж - %s',
            'header_new'              => 'Новый этаж - %s',
            'title_edit'              => 'Редактировать этаж - %s',
            'header_edit'             => 'Редактировать этаж - %s',
            'index'                   => 'Индекс',
            'mdt_sub_level'           => 'MDT подуровень',
            'floor_name'              => 'Название этажа',
            'min_enemy_size'          => 'Минимальное количество врагов (пусто по умолчанию (%s))',
            'max_enemy_size'          => 'Максимальное количество врагов (пусто по умолчанию (%s))',
            'percentage_display_zoom' => '@todo ru: .floor.edit.percentage_display_zoom',
            'default'                 => 'По умолчанию',
            'default_title'           => 'Если отмечено по умолчанию, этот этаж открывается первым при редактировании маршрутов для этого подземелья (по умолчанию должен быть отмечен только один).',
            'connected_floors'        => 'Присоединить этаж',
            'connected_floors_title'  => 'Присоединить этаж - это любой другой этаж, на который мы можем подняться с этого этажа.',
            'connected'               => 'Присоединить',
            'direction'               => 'Отсоединить',
            'floor_direction'         => [
                'none'  => 'Нет',
                'up'    => 'Верх',
                'down'  => 'Низ',
                'left'  => 'Левый',
                'right' => 'Правый',
            ],
            'submit'                  => 'Подтвердить',

            'speedrun_required_npcs' => [
                'title'                => '@todo ru: .floor.edit.speedrun_required_npcs.title',
                'add_npc'              => '@todo ru: .floor.edit.speedrun_required_npcs.add_npc',
                'table_header_id'      => '@todo ru: .floor.edit.speedrun_required_npcs.table_header_id',
                'table_header_npc'     => '@todo ru: .floor.edit.speedrun_required_npcs.table_header_npc',
                'table_header_count'   => '@todo ru: .floor.edit.speedrun_required_npcs.table_header_count',
                'table_header_actions' => '@todo ru: .floor.edit.speedrun_required_npcs.table_header_actions',
                'npc_delete'           => '@todo ru: .floor.edit.speedrun_required_npcs.npc_delete',
            ],
        ],
        'mapping' => [
            'title'  => 'Редактировать отображение - %s',
            'header' => 'Редактировать отображение - %s',
        ],
    ],
    'npc'                        => [
        'flash' => [
            'npc_updated' => 'NPC обновлены',
            'npc_created' => 'NPC %s создан',
        ],
        'edit'  => [
            'title_new'                          => 'Новый NPC',
            'header_new'                         => 'Новый NPC',
            'title_edit'                         => 'Редактировать NPC',
            'header_edit'                        => 'Редактировать NPC',
            'name'                               => 'Имя',
            'game_id'                            => 'Игровое ID',
            'classification'                     => 'Классификация',
            'aggressiveness'                     => 'Агрессивность',
            'class'                              => 'Класс',
            'base_health'                        => 'Базовое здоровье',
            'scaled_health_to_base_health_apply' => '@todo ru: .npc.edit.scaled_health_to_base_health_apply',
            'scaled_health_placeholder'          => '@todo ru: .npc.edit.scaled_health_placeholder',
            'scaled_type_none'                   => '@todo ru: .npc.edit.scaled_type_none',
            'scaled_type_fortified'              => '@todo ru: .npc.edit.scaled_type_fortified',
            'scaled_type_tyrannical'             => '@todo ru: .npc.edit.scaled_type_tyrannical',
            'enemy_forces'                       => 'Отряд врага (-1 если неизвестно)',
            'enemy_forces_teeming'               => 'Кишащий отряд врага (-1 если без изменений)',
            'dangerous'                          => 'Подземелье',
            'truesight'                          => 'Истинное зрение',
            'bursting'                           => 'Взрывной',
            'bolstering'                         => 'Усиливающий',
            'sanguine'                           => 'Кровавый',
            'bolstering_npc_whitelist'           => 'Белый список Усиливающий NPC',
            'bolstering_npc_whitelist_count'     => '{0} NPCs',
            'spells'                             => 'Способность',
            'spells_count'                       => '{0} Способность',
            'submit'                             => 'Подтвердить',
            'save_as_new_npc'                    => 'Сохранить нового NPC',
            'all_npcs'                           => 'Все NPC',
            'all_dungeons'                       => 'Все подземелья',
        ],
        'list'  => [
            'all_dungeons'                => 'Все',
            'title'                       => 'Список NPC',
            'header'                      => 'Показать NPC',
            'create_npc'                  => 'Создать NPC',
            'table_header_id'             => 'ID',
            'table_header_name'           => 'Имя',
            'table_header_dungeon'        => 'Подземелье',
            'table_header_enemy_forces'   => 'Отряд врага',
            'table_header_enemy_count'    => 'Счетчик врагов',
            'table_header_classification' => 'Классификация',
            'table_header_actions'        => 'Действия',
        ],
    ],
    'release'                    => [
        'edit' => [
            'title_new'   => 'Новый релиз',
            'header_new'  => 'Новый релиз',
            'title_edit'  => 'Редактировать релиз',
            'header_edit' => 'Редактировать релиз',
            'version'     => 'Версия',
            'title'       => 'Название',
            'silent'      => 'Немой',
            'spotlight'   => 'Подсветка',
            'changelog'   => 'Список изменений',
            'description' => 'Описание',
            'ticket_nr'   => 'Обращение №',
            'change'      => 'Изменения',
            'add_change'  => 'Добавить изменение',
            'edit'        => 'Редактировать',
            'submit'      => 'Подтвердить',
        ],
        'list' => [
            'title'                => 'Список релизов',
            'view_releases'        => 'Показать релизы',
            'create_release'       => 'Создать релиз',
            'table_header_id'      => 'ID',
            'table_header_version' => 'Версия',
            'table_header_title'   => 'Название',
            'table_header_actions' => 'Действия',
            'edit'                 => 'Редактировать',
        ],
    ],
    'spell'                      => [
        'edit' => [
            'title_new'         => 'Новая способность',
            'header_new'        => 'Новая способность',
            'title_edit'        => 'Редактировать способность',
            'header_edit'       => 'Редактировать способность',
            'game_id'           => 'Игровое ID',
            'name'              => 'Название',
            'icon_name'         => 'Название иконки',
            'dispel_type'       => 'Тип развеивания',
            'schools'           => 'Школа',
            'aura'              => 'Аура',
            'submit'            => 'Подтвердить',
            'save_as_new_spell' => 'Сохранить как новую способность',
        ],
        'list' => [
            'title'                => 'Список способностей',
            'header'               => 'Показать способность',
            'create_spell'         => 'Создать способность',
            'table_header_icon'    => 'Иконка',
            'table_header_id'      => 'ID',
            'table_header_name'    => 'Название',
            'table_header_actions' => 'Действия',
            'edit'                 => 'Редактировать',
        ],
    ],
    'tools'                      => [
        'datadump'     => [
            'viewexporteddungeondata' => [
                'title'   => 'Экспортировано!',
                'header'  => 'Данные подземелья сброшены',
                'content' => 'Экспортировано!',
            ],
            'viewexportedrelease'     => [
                'title'   => 'Экспортировано!',
                'header'  => 'Данные подземелья сброшены',
                'content' => 'Экспортировано!',
            ],
        ],
        'dungeonroute' => [
            'view'         => [
                'title'      => 'Показать подземелье',
                'header'     => 'Показать подземелье',
                'public_key' => 'Публичный ключ маршрута подземелья',
                'submit'     => 'Подтвердить',
            ],
            'viewcontents' => [
                'title'  => 'Просмотреть содержимое для :dungeonRouteTitle',
                'header' => 'Просмотреть содержимое для %s',
            ],
        ],
        'enemyforces'  => [
            'title'                    => 'Импорт силы врага',
            'header'                   => 'Импорт силы врага',
            'paste_mennos_export_json' => 'Вставить Menno\'s экспортированный Json',
            'submit'                   => 'Отправить',
            'recalculate'              => [
                'title'  => '@todo ru: .tools.enemyforces.recalculate.title',
                'header' => '@todo ru: .tools.enemyforces.recalculate.header',
                'submit' => '@todo ru: .tools.enemyforces.recalculate.submit',
            ],
        ],
        'exception'    => [
            'select' => [
                'title'                     => 'Сброс исключений',
                'header'                    => 'Сброс исключений',
                'select_exception_to_throw' => 'Выберите исключение, которое нужно сбросить',
                'submit'                    => 'Подтвердить',
            ],
        ],
        'mdt'          => [
            'diff'                              => [
                'title'                 => 'MDT Различия',
                'header'                => 'MDT Различия',
                'headers'               => [
                    'mismatched_health'               => 'Здоровье не соответствует',
                    'mismatched_enemy_count'          => 'Количества врагов не соответствует',
                    'mismatched_enemy_type'           => 'Тип врага не соответствует ',
                    'missing_npc'                     => 'Отсутствует NPC',
                    'mismatched_enemy_forces'         => 'Отсутствует отряд врага',
                    'mismatched_enemy_forces_teeming' => 'Отсутствует отряд врага (Кишащий)',
                ],
                'table_header_dungeon'  => 'Подземелье',
                'table_header_npc'      => 'NPC',
                'table_header_message'  => 'Сообщение',
                'table_header_actions'  => 'Действия',
                'no_dungeon_name_found' => 'Название подземелья не найдено',
                'no_npc_name_found'     => 'Название NPC не найдено',
                'npc_message'           => ':npcName (:npcId, :count использованы)',
                'apply_mdt_kg'          => 'Применить (MDT -> KG)',
            ],
            'dungeonroute'                      => [
                'title'      => 'Просмотреть маршрут подземелья как строку для MDT',
                'header'     => 'Просмотреть маршрут подземелья как строку для MDT',
                'public_key' => 'Публичный ключ',
                'submit'     => 'Подтвердить',
            ],
            'string'                            => [
                'title'                        => 'Просмотр содержимое строки MDT',
                'header'                       => 'Просмотр содержимое строки MDT',
                'paste_your_mdt_export_string' => 'Вставьте строку экспорта Mythic Dungeon Tools',
                'submit'                       => 'Подтвердить',
            ],
            'dungeonmappinghash'                => [
                'title'  => '@todo ru: .tools.mdt.dungeonmappinghash.title',
                'header' => '@todo ru: .tools.mdt.dungeonmappinghash.header',
                'submit' => '@todo ru: .tools.mdt.dungeonmappinghash.submit',
            ],
            'dungeonmappingversiontomdtmapping' => [
                'title'  => '@todo ru: .tools.mdt.dungeonmappingversiontomdtmapping.title',
                'header' => '@todo ru: .tools.mdt.dungeonmappingversiontomdtmapping.header',
                'submit' => '@todo ru: .tools.mdt.dungeonmappingversiontomdtmapping.submit',
            ],
        ],
        'npcimport'    => [
            'title'                   => 'Массовый импорт NPC',
            'header'                  => 'Массовый импорт NPC',
            'paste_npc_import_string' => 'Вставьте строку импорта NPC',
            'submit'                  => 'Подтвердить',
        ],
        'list'         => [
            'title'            => 'Инструменты администратора',
            'header'           => 'Инструменты администратора',
            'header_tools'     => 'Инструменты',
            'subheader_import' => 'Импорт',
            'mass_import_npcs' => 'Массовый импорт NPC',

            'subheader_dungeonroute'    => 'Маршрут подземелья',
            'view_dungeonroute_details' => 'Показать детали маршрута подземелья',

            'subheader_mdt'                               => 'MDT',
            'view_mdt_string'                             => 'Просмотреть содержимое строки MDT',
            'view_mdt_string_as_dungeonroute'             => 'Просмотреть строку MDT как маршрут подземелья',
            'view_dungeonroute_as_mdt_string'             => 'Просмотреть маршрут подземелья как строку MDT',
            'view_mdt_diff'                               => 'Просмотр различия с MDT',
            'view_dungeon_mapping_hash'                   => '@todo ru: .tools.list.view_dungeon_mapping_hash',
            'view_dungeon_mapping_version_to_mdt_mapping' => '@todo ru: .tools.list.view_dungeon_mapping_version_to_mdt_mapping',

            'subheader_enemy_forces' => 'Силы врага',
            'enemy_forces_import'    => 'Импорт силы врага',

            'subheader_wowtools'                 => '@todo ru: .tools.list.subheader_wowtools',
            'wowtools_import_ingame_coordinates' => '@todo ru: .tools.list.wowtools_import_ingame_coordinates',

            'subheader_misc'     => 'Разное',
            'drop_caches'        => 'Сбросить кеш',
            'throw_an_exception' => 'Сбросить исключения',

            'subheader_mapping'  => '@todo ru: .tools.list.subheader_mapping',
            'force_sync_mapping' => '@todo ru: .tools.list.force_sync_mapping',

            'subheader_actions'   => 'Действия',
            'export_dungeon_data' => 'Экспорт данных о подземельях',
            'export_releases'     => 'Экспорт релизов',

            'enemy_forces_recalculate' => '@todo ru: .tools.list.enemy_forces_recalculate',

            'subheader_thumbnails'  => '@todo ru: .tools.list.subheader_thumbnails',
            'thumbnails_regenerate' => '@todo ru: .tools.list.thumbnails_regenerate',
        ],
        'thumbnails'   => [
            'regenerate' => [
                'title'  => '@todo ru: .tools.thumbnails.regenerate.title',
                'header' => '@todo ru: .tools.thumbnails.regenerate.header',
                'submit' => '@todo ru: .tools.thumbnails.regenerate.submit',
            ],
        ],
        'wowtools'     => [
            'importingamecoordinates' => [
                'title'                   => '@todo ru: .tools.wowtools.importingamecoordinates.title',
                'header'                  => '@todo ru: .tools.wowtools.importingamecoordinates.header',
                'map_csv'                 => '@todo ru: .tools.wowtools.importingamecoordinates.map_csv',
                'ui_map_group_member_csv' => '@todo ru: .tools.wowtools.importingamecoordinates.ui_map_group_member_csv',
                'ui_map_assignment_csv'   => '@todo ru: .tools.wowtools.importingamecoordinates.ui_map_assignment_csv',
                'submit'                  => '@todo ru: .tools.wowtools.importingamecoordinates.submit',
            ],
        ],
    ],
    'user'                       => [
        'list' => [
            'title'                   => 'Список пользователей',
            'header'                  => 'Показать пользователя',
            'table_header_id'         => 'ID',
            'table_header_name'       => 'Имя',
            'table_header_email'      => 'Email',
            'table_header_routes'     => 'Маршруты',
            'table_header_roles'      => 'Роли',
            'table_header_registered' => 'Зарегистрирован',
            'table_header_actions'    => 'Действия',
            'table_header_patreons'   => 'Patreon',
        ],
    ],
    'userreport'                 => [
        'list' => [
            'title'                    => 'Отчеты пользователей',
            'header'                   => 'Просмотр отчетов пользователей',
            'table_header_id'          => 'ID',
            'table_header_author_name' => 'Имя автора',
            'table_header_category'    => 'Категория',
            'table_header_message'     => 'Сообщение',
            'table_header_contact_at'  => 'Адрес для связи',
            'table_header_create_at'   => 'Создано',
            'table_header_actions'     => 'Действия',
            'handled'                  => 'Обработано',
        ],
    ],
];