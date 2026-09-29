<?php

return [
    'directory' => [
        'title'                   => '路线创作者',
        'header'                  => '路线创作者',
        'description'             => '浏览在 Keystone.guru 上创作路线的玩家。已发布至少 :min 条路线的创作者会自动列出——打开一个资料即可查看其置顶路线以及在其他地方的联系方式。',
        'search_label'            => '搜索创作者',
        'search_placeholder'      => '按名称搜索',
        'search_submit'           => '搜索',
        'category_label'          => '路线面向',
        'category_any'            => '所有玩家',
        'category_option'         => ':category玩家',
        'sort_label'              => '排序方式',
        'sort_active_this_season' => '本赛季活跃',
        'sort_most_routes'        => '路线最多',
        'empty'                   => '目前还没有列出的创作者。',
        'empty_for_category'      => '目前还没有创作者共享":category"合集。',
        'empty_for_search'        => '未找到与":search"匹配的创作者。',
    ],
    'featured' => [
        'title'         => '精选创作者',
        'title_dungeon' => ':dungeon 的创作者',
        'see_all'       => '查看所有创作者',
        /** Tooltip on a rail entry - it carries the name because the name itself may be clipped to an ellipsis */
        'entry_title'         => ':name - :routes',
        'route_count'         => '{1} :count 条路线|[2,*] :count 条路线',
        'dungeon_route_count' => '{1} :count 条 :dungeon 路线|[2,*] :count 条 :dungeon 路线',
    ],
    'stats' => [
        'route_count'              => '{0} 没有路线|{1} :count 条路线|[2,*] :count 条路线',
        'route_count_total'        => '{0} 没有已发布的路线|{1} 共 :count 条路线|[2,*] 共 :count 条路线',
        'season_route_count'       => '{1} 本赛季 :count 条路线|[2,*] 本赛季 :count 条路线',
        'season_route_count_named' => '{0} :season：没有路线|{1} :season：:count 条路线|[2,*] :season：:count 条路线',
        'no_routes_this_season'    => '本赛季没有路线',
        'views'                    => '{0} 没有浏览|{1} :views 次浏览|[2,*] :views 次浏览',
        'rating'                   => '{1} ★ :rating（:count 个评分）|[2,*] ★ :rating（:count 个评分）',
        'last_published'           => '最近发布：:time',
    ],
];
