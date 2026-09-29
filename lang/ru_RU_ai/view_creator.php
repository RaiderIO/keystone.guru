<?php

return [
    'directory' => [
        'title'                   => 'Создатели маршрутов',
        'header'                  => 'Создатели маршрутов',
        'description'             => 'Просмотрите список людей, создающих маршруты на Keystone.guru. Создатели с опубликованными маршрутами отображаются автоматически - откройте профиль, чтобы увидеть их закрепленные маршруты и другие способы связи с ними.',
        'search_label'            => 'Поиск создателя',
        'search_placeholder'      => 'Поиск по имени',
        'search_submit'           => 'Поиск',
        'category_label'          => 'Фильтр по типу коллекций, которыми делится создатель',
        'category_any'            => 'Любая коллекция',
        'category_option'         => '',
        'sort_label'              => '',
        'sort_active_this_season' => '',
        'sort_most_routes'        => '',
        'empty'                   => 'Создатели пока не найдены.',
        'empty_for_category'      => 'Пока никто из создателей не делится коллекцией ":category".',
        'empty_for_search'        => 'Не найдено создателей по запросу ":search".',
    ],
    'featured' => [
        'title'         => 'Избранные создатели',
        'title_dungeon' => '',
        'see_all'       => 'Показать всех создателей',
        /** Tooltip on a rail entry - it carries the name because the name itself may be clipped to an ellipsis */
        'entry_title'         => ':name - :routes',
        'route_count'         => '',
        'dungeon_route_count' => '',
    ],
    'stats' => [
        'route_count'              => '',
        'route_count_total'        => '',
        'season_route_count'       => '',
        'season_route_count_named' => '',
        'no_routes_this_season'    => '',
        'views'                    => '',
        'rating'                   => '',
        'last_published'           => '',
    ],
];
