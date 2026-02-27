<?php

return [
    'dungeonroute' => [
        'search' => [
            'gameversion' => [
                'dungeon' => [
                    'title'                                  => '',
                    'description'                            => '',
                    'linkpreview_default_description_search' => '',
                    'linkpreview_title'                      => '',
                ],
            ],
            'list' => [
                'title'       => '',
                'header'      => '',
                'description' => '',
            ],
        ],
    ],
    'explore' => [
        'gameversion' => [
            'list' => [
                'title'             => 'Огляд',
                'header'            => 'Огляд підземелля',
                'description'       => 'За допомогою огляду можна дослідити планування підземелля й наявних ворогів. Ідеальний варіант, коли ви просто хочете ознайомитися з місцевістю без створення маршруту.',
                'heatmap_available' => 'Доступна тепломапа',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => 'Будь-яке',
                'select_floor'            => 'Вибрати поверх',
                'view_heatmap_fullscreen' => 'На весь екран',
            ],
            'view' => [
                'title' => '',
            ],
        ],
    ],
    'heatmap' => [
        'gameversion' => [
            'list' => [
                'title'             => 'Тепломапи',
                'header'            => 'Тепломапи підземель',
                'raider_io'         => 'Raider.IO',
                'description'       => 'Тепломапи від :raiderIO надають цінну інформацію про те, де гравці помирають, застосовують певні закляття та яких ворогів убивають. Фільтри за рівнем ключа, рівнем предметів, складом команди тощо дозволяють зосередитися на потрібних даних.',
                'heatmap_available' => 'Доступна тепломапа',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => 'Будь-яке',
                'select_floor'            => 'Вибрати поверх',
                'view_heatmap_fullscreen' => 'На весь екран',
            ],
        ],
    ],

];
