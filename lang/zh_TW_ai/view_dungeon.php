<?php

return [

    'dungeonroute' => [
        'search' => [
            'gameversion' => [
                'dungeon' => [
                    'title'                                  => '搜索:dungeon的路線',
                    'description'                            => '按標題、鑰匙等級、詞綴、敵人力量、評分等搜索路線。選擇一個地城以開始。',
                    'linkpreview_default_description_search' => '尋找M+路線以8669aa7cd1b0fd99996edd73 | Keystone.guru',
                    'linkpreview_title'                      => ':title | Keystone.guru',
                ],
            ],
            'list' => [
                'title'       => '搜索路線',
                'header'      => '搜索路線',
                'description' => '按標題、鑰匙等級、詞綴、敵人力量、評分等搜索路線。選擇一個地城以開始。',
            ],
        ],
    ],
    'explore' => [
        'gameversion' => [
            'list' => [
                'title'             => '探索',
                'header'            => '探索地城',
                'description'       => '探索地城可以讓你看到地城的布局和存在的敵人。非常適合僅查看地城而不創建路線。',
                'heatmap_available' => '地圖熱點可用於地城',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => '任何',
                'select_floor'            => '選擇樓層',
                'view_heatmap_fullscreen' => '全屏查看熱圖',
            ],
            'view' => [
                'title' => '探索 :dungeon',
            ],
        ],
    ],
    'heatmap' => [
        'gameversion' => [
            'list' => [
                'title'             => '地圖熱點',
                'header'            => '地城地圖熱點',
                'raider_io'         => 'Raider.IO',
                'description'       => '由 :raiderIO 提供支持，地圖熱點可以向您展示哪些敵人被玩家擊敗、他們在哪裡被擊敗或施放某些法術。關鍵等級、物品等級、隊伍組成等多種篩選器可讓您專注於與您需求相關的數據。',
                'heatmap_available' => '地圖熱點可用於地城',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => '任何',
                'select_floor'            => '選擇樓層',
                'view_heatmap_fullscreen' => '全螢幕查看',
            ],
        ],
    ],

];
