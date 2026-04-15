<?php

return [
    'dungeonroute' => [
        'search' => [
            'gameversion' => [
                'dungeon' => [
                    'title'                                  => '搜索:dungeon的路线',
                    'description'                            => '按标题、钥匙等级、词缀、敌人力量、评分等搜索路线。选择一个地下城开始。',
                    'linkpreview_default_description_search' => '查找:dungeon的M+路线',
                    'linkpreview_title'                      => ':title | Keystone.guru',
                ],
            ],
            'list' => [
                'title'       => '搜索路线',
                'header'      => '搜索路线',
                'description' => '按标题、钥匙等级、词缀、敌人力量、评分等搜索路线。选择一个地下城开始。',
            ],
        ],
    ],
    'explore' => [
        'gameversion' => [
            'list' => [
                'title'             => '探索',
                'header'            => '探索地下城',
                'description'       => '探索地下城可查看地下城布局及存在的敌人。适合仅查看地下城而不创建路线。',
                'heatmap_available' => '地下城的热图可用',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => '任何',
                'select_floor'            => '选择楼层',
                'view_heatmap_fullscreen' => '全屏查看热图',
            ],
            'view' => [
                'title' => '探索:dungeon',
            ],
        ],
    ],
    'heatmap' => [
        'gameversion' => [
            'list' => [
                'title'             => '热图',
                'header'            => '地下城热图',
                'raider_io'         => 'Raider.IO',
                'description'       => '由:raiderIO提供支持，热图可以为您提供无价的信息，帮助您了解玩家在哪些地方击败敌人，在哪里被击杀或施放特定的法术。通过钥石等级、物品等级、团队组成等多种过滤器，您可以专注于与您需求相关的数据。',
                'heatmap_available' => '地下城的热图可用',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => '任何',
                'select_floor'            => '选择楼层',
                'view_heatmap_fullscreen' => '全屏查看',
            ],
        ],
    ],

];
