<?php

return [
    'directory' => [
        'title'                   => 'Создатели маршрутов',
        'header'                  => 'Создатели маршрутов',
        'description'             => 'Просмотрите список людей, создающих маршруты на Keystone.guru. Создатели, у которых опубликованных маршрутов не меньше :min, отображаются автоматически - откройте профиль, чтобы увидеть их закрепленные маршруты и другие способы связи с ними.',
        'search_label'            => 'Поиск создателя',
        'search_placeholder'      => 'Поиск по имени',
        'search_submit'           => 'Поиск',
        'category_label'          => 'Создает маршруты для',
        'category_any'            => 'Всех игроков',
        'category_option'         => 'Игроков уровня «:category»',
        'sort_label'              => 'Сортировка',
        'sort_active_this_season' => 'Активные в этом сезоне',
        'sort_most_routes'        => 'Больше всего маршрутов',
        'empty'                   => 'Создатели пока не найдены.',
        'empty_for_category'      => 'Пока никто из создателей не делится коллекцией ":category".',
        'empty_for_search'        => 'Не найдено создателей по запросу ":search".',
        'empty_for_dungeon'       => 'Пока никто из создателей не публикует маршруты для подземелья «:dungeon».',
        'filtered_to_dungeon'     => 'Создатели маршрутов для подземелья «:dungeon»; сначала те, чьи маршруты там популярнее всего.',
        'clear_dungeon_filter'    => 'Все подземелья',
    ],
    'featured' => [
        'title'           => 'Избранные создатели',
        'title_dungeon'   => 'Создатели маршрутов: :dungeon',
        'see_all_dungeon' => 'Все создатели маршрутов для подземелья «:dungeon»',
        /** Tooltip on a rail entry - it carries the name because a name past two lines is clipped to an ellipsis */
        'entry_title'         => ':name - :routes',
        'route_count'         => '{1} :count маршрут|[2,*] Маршрутов: :count',
        'dungeon_route_count' => '{1} :count маршрут (:dungeon)|[2,*] Маршрутов (:dungeon): :count',
    ],
    'stats' => [
        'route_count'              => '{0} Нет маршрутов|{1} :count маршрут|[2,*] Маршрутов: :count',
        'route_count_total'        => '{0} Нет опубликованных маршрутов|{1} Всего :count маршрут|[2,*] Всего маршрутов: :count',
        'season_route_count'       => '{1} :count маршрут в этом сезоне|[2,*] Маршрутов в этом сезоне: :count',
        'season_route_count_named' => '{0} Нет маршрутов (:season)|{1} :count маршрут (:season)|[2,*] Маршрутов (:season): :count',
        'no_routes_this_season'    => 'Нет маршрутов в этом сезоне',
        'views'                    => '{0} Нет просмотров|{1} :views просмотр|[2,*] Просмотров: :views',
        'rating'                   => '{1} ★ :rating (:count оценка)|[2,*] ★ :rating (оценок: :count)',
        'last_published'           => 'Последняя публикация: :time',
    ],
];
