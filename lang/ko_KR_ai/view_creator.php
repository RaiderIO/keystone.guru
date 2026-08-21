<?php

return [
    'directory' => [
        'title'              => '경로 제작자',
        'header'             => '경로 제작자',
        'description'        => 'Keystone.guru에서 경로를 제작하는 사람들을 둘러보세요. 게시된 경로가 있는 제작자는 자동으로 목록에 표시됩니다. 프로필을 열어 고정된 경로와 다른 곳에서 그들을 찾을 수 있는 방법을 확인해 보세요.',
        'search_label'       => '제작자 검색',
        'search_placeholder' => '이름으로 검색',
        'search_submit'      => '검색',
        'empty'              => '아직 등록된 제작자가 없습니다.',
        'empty_for_search'   => '":search"와(과) 일치하는 제작자를 찾을 수 없습니다.',
    ],
    'featured' => [
        'title'   => '추천 제작자',
        'see_all' => '모든 제작자 보기',
        /** Tooltip on a rail entry - it carries the name because the name itself may be clipped to an ellipsis */
        'entry_title' => ':name - :routes',
    ],
    'card' => [
        'route_count' => '{0} 게시된 경로 없음|{1} :count개의 게시된 경로|[2,*] :count개의 게시된 경로',
    ],
];
