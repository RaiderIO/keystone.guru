<?php

return [
    'directory' => [
        'title'                   => '경로 제작자',
        'header'                  => '경로 제작자',
        'description'             => 'Keystone.guru에서 경로를 제작하는 사람들을 둘러보세요. 게시된 경로가 :min개 이상인 제작자는 자동으로 목록에 표시됩니다. 프로필을 열어 고정된 경로와 다른 곳에서 그들을 찾을 수 있는 방법을 확인해 보세요.',
        'search_label'            => '제작자 검색',
        'search_placeholder'      => '이름으로 검색',
        'search_submit'           => '검색',
        'category_label'          => '경로 제작 대상',
        'category_any'            => '모든 플레이어',
        'category_option'         => ':category 플레이어',
        'sort_label'              => '정렬 기준',
        'sort_active_this_season' => '이번 시즌 활동',
        'sort_most_routes'        => '경로 많은 순',
        'empty'                   => '아직 등록된 제작자가 없습니다.',
        'empty_for_category'      => '아직 ":category" 컬렉션을 공유하는 제작자가 없습니다.',
        'empty_for_search'        => '":search"와(과) 일치하는 제작자를 찾을 수 없습니다.',
    ],
    'featured' => [
        'title'         => '추천 제작자',
        'title_dungeon' => ':dungeon 제작자',
        'see_all'       => '모든 제작자 보기',
        /** Tooltip on a rail entry - it carries the name because the name itself may be clipped to an ellipsis */
        'entry_title'         => ':name - :routes',
        'route_count'         => '{1} 경로 :count개|[2,*] 경로 :count개',
        'dungeon_route_count' => '{1} :dungeon 경로 :count개|[2,*] :dungeon 경로 :count개',
    ],
    'stats' => [
        'route_count'              => '{0} 경로 없음|{1} 경로 :count개|[2,*] 경로 :count개',
        'route_count_total'        => '{0} 게시된 경로 없음|{1} 총 경로 :count개|[2,*] 총 경로 :count개',
        'season_route_count'       => '{1} 이번 시즌 경로 :count개|[2,*] 이번 시즌 경로 :count개',
        'season_route_count_named' => '{0} :season 경로 없음|{1} :season 경로 :count개|[2,*] :season 경로 :count개',
        'no_routes_this_season'    => '이번 시즌 경로 없음',
        'views'                    => '{0} 조회수 없음|{1} 조회수 :views|[2,*] 조회수 :views',
        'rating'                   => '{1} ★ :rating (평가 :count개)|[2,*] ★ :rating (평가 :count개)',
        'last_published'           => '마지막 게시: :time',
    ],
];
