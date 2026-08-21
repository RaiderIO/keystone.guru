<?php

return [
    'directory' => [
        'title'              => '路线创作者',
        'header'             => '路线创作者',
        'description'        => '浏览在 Keystone.guru 上创作路线的玩家。已发布路线的创作者会自动列出——打开一个资料即可查看其置顶路线以及在其他地方的联系方式。',
        'search_label'       => '搜索创作者',
        'search_placeholder' => '按名称搜索',
        'search_submit'      => '搜索',
        'empty'              => '目前还没有列出的创作者。',
        'empty_for_search'   => '未找到与":search"匹配的创作者。',
    ],
    'featured' => [
        'title'   => '精选创作者',
        'see_all' => '查看所有创作者',
        /** Tooltip on a rail entry - it carries the name because the name itself may be clipped to an ellipsis */
        'entry_title' => ':name - :routes',
    ],
    'card' => [
        'route_count' => '{0} 没有已发布的路线|{1} :count 条已发布的路线|[2,*] :count 条已发布的路线',
    ],
];
