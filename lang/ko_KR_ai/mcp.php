<?php

return [
    'error' => [
        'unauthenticated'                   => '인증되지 않았습니다. OAuth 액세스 토큰으로 연결하세요.',
        'feature_inactive'                  => '이 계정에서는 keystone.guru MCP 서버를 사용할 수 없습니다.',
        'missing_scope'                     => '액세스 토큰에 이 도구에 필요한 :scope 권한 범위가 없습니다. 다시 연결하여 권한을 부여하세요.',
        'rate_limited'                      => '이 도구에 대한 요청이 너무 많습니다. :seconds초 후에 다시 시도하세요.',
        'request_refused'                   => '요청이 거부되었습니다.',
        'dungeon_not_found'                 => '":dungeon"과(와) 일치하는 활성 던전이 없습니다. 유효한 키는 list_dungeons로 확인하세요.',
        'route_not_found'                   => '키가 ":route"인 경로가 없습니다. 내 경로는 list_my_routes로 확인하세요.',
        'pull_not_found'                    => '경로에 풀 :pull이(가) 없습니다.',
        'unable_to_save_route'              => '경로를 저장하지 못했습니다.',
        'unable_to_save_pull'               => '풀을 저장하지 못했습니다.',
        'unable_to_delete_pull'             => '풀을 제거하지 못했습니다.',
        'no_enemies_given'                  => '묶음 ID 또는 적 ID를 하나 이상 입력하세요.',
        'unknown_pack_ids'                  => '다음 묶음 ID는 경로의 던전 매핑에 속하지 않습니다: :ids.',
        'unknown_enemy_ids'                 => '다음 적 ID는 경로의 던전 매핑에 속하지 않습니다: :ids.',
        'enemies_already_pulled'            => '다음 적은 이미 다른 풀에 있습니다(적 ID => 풀 번호): :enemies. 먼저 해당 풀에서 제거하거나 제외하세요.',
        'unknown_spell_ids'                 => '다음 주문 ID는 풀에서 시전할 수 없습니다: :ids. 풀에 넣을 수 있는 주문은 get_route_options로 확인하세요.',
        'unknown_floor'                     => '층 :floor은(는) 경로 던전의 층이 아닙니다. 층 목록은 list_dungeons로 확인하세요.',
        'unknown_dungeon_difficulty'        => '난이도 :difficulty은(는) 이 던전의 스피드런 난이도가 아닙니다. 각 던전의 난이도는 list_dungeons로 확인하세요.',
        'unknown_dungeon_floor'             => '층 :floor은(는) 이 던전의 층이 아닙니다. 층 목록은 list_dungeons로 확인하세요.',
        'unknown_dungeon_start'             => '던전 시작 :start은(는) 경로의 던전 매핑에 속하지 않습니다. 던전의 시작 지점은 get_route_options에 던전을 지정하여 확인하세요.',
        'specialization_not_of_class'       => '파티원 :member의 전문화가 다른 클래스의 전문화입니다.',
        'specialization_not_of_spell_class' => '전문화 :specialization은(는) :class 클래스의 전문화가 아닙니다. 각 클래스의 전문화는 get_route_options로 확인하세요.',
        'reorder_mismatch'                  => '순서에는 경로의 모든 풀 ID가 정확히 한 번씩 포함되어야 합니다. 경로의 풀 ID: :ids.',
    ],
];
