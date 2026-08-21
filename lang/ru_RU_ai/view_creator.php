<?php

return [
    'directory' => [
        'title'              => 'Создатели маршрутов',
        'header'             => 'Создатели маршрутов',
        'description'        => 'Просмотрите список людей, создающих маршруты на Keystone.guru. Создатели с опубликованными маршрутами отображаются автоматически - откройте профиль, чтобы увидеть их закрепленные маршруты и другие способы связи с ними.',
        'search_label'       => 'Поиск создателя',
        'search_placeholder' => 'Поиск по имени',
        'search_submit'      => 'Поиск',
        'empty'              => 'Создатели пока не найдены.',
        'empty_for_search'   => 'Не найдено создателей по запросу ":search".',
    ],
    'featured' => [
        'title'   => 'Избранные создатели',
        'see_all' => 'Показать всех создателей',
        /** Tooltip on a rail entry - it carries the name because the name itself may be clipped to an ellipsis */
        'entry_title' => ':name - :routes',
    ],
    'card' => [
        'route_count' => '{0} Нет опубликованных маршрутов|{1} :count опубликованный маршрут|[2,*] :count опубликованных маршрутов',
    ],
];
