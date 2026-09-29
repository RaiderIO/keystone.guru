<?php

return [
    'directory' => [
        'title'                   => '路线创作者',
        'header'                  => '路线创作者',
        'description'             => '浏览在 Keystone.guru 上创作路线的玩家。已发布路线的创作者会自动列出——打开一个资料即可查看其置顶路线以及在其他地方的联系方式。',
        'search_label'            => '搜索创作者',
        'search_placeholder'      => '按名称搜索',
        'search_submit'           => '搜索',
        'category_label'          => '按创作者共享的合集类型筛选',
        'category_any'            => '任意合集',
        'category_option'         => '',
        'sort_label'              => '',
        'sort_active_this_season' => '',
        'sort_most_routes'        => '',
        'empty'                   => '目前还没有列出的创作者。',
        'empty_for_category'      => '目前还没有创作者共享":category"合集。',
        'empty_for_search'        => '未找到与":search"匹配的创作者。',
    ],
    'featured' => [
        'title'         => '精选创作者',
        'title_dungeon' => '',
        'see_all'       => '查看所有创作者',
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
