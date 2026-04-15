<?php

return [
    'dungeonroute' => [
        'search' => [
            'gameversion' => [
                'dungeon' => [
                    'title'                                  => ':dungeon에 대한 경로 검색',
                    'description'                            => '제목, 키 레벨, 속성, 적의 힘, 평점 등을 기준으로 경로를 검색하세요. 시작하려면 던전을 선택하세요.',
                    'linkpreview_default_description_search' => ':dungeon에 대한 M+ 경로 찾기',
                    'linkpreview_title'                      => ':title | Keystone.guru',
                ],
            ],
            'list' => [
                'title'       => '경로 검색',
                'header'      => '경로 검색',
                'description' => '제목, 키 레벨, 속성, 적의 힘, 평점 등을 기준으로 경로를 검색하세요. 시작하려면 던전을 선택하세요.',
            ],
        ],
    ],
    'explore' => [
        'gameversion' => [
            'list' => [
                'title'             => '탐험',
                'header'            => '던전 탐험',
                'description'       => '던전을 탐험하면 던전의 레이아웃과 존재하는 적들을 볼 수 있습니다. 경로를 만들지 않고 던전을 간단히 보기에는 이상적입니다.',
                'heatmap_available' => '던전에 대한 히트맵 사용 가능',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => '모두',
                'select_floor'            => '층 선택',
                'view_heatmap_fullscreen' => '전체 화면 보기',
            ],
            'view' => [
                'title' => ':dungeon 탐색',
            ],
        ],
    ],
    'heatmap' => [
        'gameversion' => [
            'list' => [
                'title'             => '히트맵',
                'header'            => '던전 히트맵',
                'raider_io'         => 'Raider.IO',
                'description'       => ':raiderIO에 의해 제공되는 히트맵은 플레이어가 처치한 적이나, 어디서 사망했는지, 또는 특정 주문을 시전했는지에 대한 귀중한 정보를 보여줍니다. 키 레벨, 아이템 레벨, 팀 구성 등 다양한 필터를 통해 필요한 데이터에 집중할 수 있습니다.',
                'heatmap_available' => '던전에 대한 히트맵 사용 가능',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => '모두',
                'select_floor'            => '층 선택',
                'view_heatmap_fullscreen' => '전체 화면 보기',
            ],
        ],
    ],

];
