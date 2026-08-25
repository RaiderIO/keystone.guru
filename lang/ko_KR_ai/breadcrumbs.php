<?php

return [

    'home' => [
        'front_page' => 'Keystone.guru',
        'compendium' => [
            'npc'          => 'NPC 도감',
            'npc_show'     => ':name',
            'spell'        => '주문 도감',
            'spell_show'   => ':name',
            'activity'     => '도감 활동',
            'activity_day' => ':date',
            'tuning'       => '주문 수치 조정',
            'class'        => '직업별',
        ],
        'affixes' => '접두사',
        'about'   => '정보',
        'credits' => '크레딧',
        'legal'   => [
            'cookies' => '쿠키',
            'privacy' => '개인 정보 보호',
            'terms'   => '이용 약관',
        ],
        'routes'              => '경로',
        'routes_expansion'    => ':expansion 경로',
        'routes_game_version' => ':gameVersion 경로',
        'gameversion'         => [
            'update'  => ':gameVersion',
            'dungeon' => [
                'heatmap' => '히트맵',
                'explore' => '탐색',
            ],
        ],
        'dungeonroute' => [
            'new' => '새 경로',
        ],
        'dungeonroutes' => [
            'search'        => '검색',
            'popular'       => '인기',
            'new'           => '새로운',
            'routes_season' => '시즌 :season',
            'season'        => [
                'popular' => '인기',
                'new'     => '새로운',
            ],
            'discoverdungeon' => [
                'popular' => '인기',
                'new'     => '새로운',
            ],
        ],
        'my_favorites'     => '내 즐겨찾기',
        'account_settings' => '계정 설정',
        'my_routes'        => '내 경로',
        'my_tags'          => '내 태그',
        'my_teams'         => '내 팀',
        'overview'         => '개요',
        'new_team'         => '새 팀',
        'edit_team'        => '팀 편집',
        'join_team'        => '팀 가입',
        'admin'            => [
            'admin'   => '관리자',
            'affixes' => [
                'affixes'    => '접사',
                'new_affix'  => '새 접사',
                'edit_affix' => ':affix 편집',
            ],
            'seasons' => [
                'seasons'     => '시즌',
                'new_season'  => '새 시즌',
                'edit_season' => ':season 편집',
            ],
            'affixgroups' => [
                'new_affixgroup'  => '새 접사 그룹',
                'edit_affixgroup' => '접사 그룹 편집',
            ],
            'tools' => [
                'admin_tools'                                 => '관리자 도구',
                'view_exported_dungeondata'                   => '내보낸 던전 데이터 보기',
                'select_exception'                            => '예외 선택',
                'mdt_diff'                                    => 'MDT 차이',
                'view_mdt_string_contents'                    => 'MDT 문자열 내용 보기',
                'import_npcs'                                 => 'NPC 가져오기',
                'spells_missing_info'                         => '정보가 누락된 주문',
                'npcs_missing_display_id'                     => '디스플레이 ID가 누락된 NPC',
                'thumbnails_regenerate'                       => '썸네일 재생성',
                'combat_log_regenerate'                       => 'ARC 경로 재생성',
                'combat_log_criteria'                         => 'NPC 도감 파싱 기준',
                'combat_log_run_data'                         => '전투 기록 런 데이터 정리',
                'combat_log_route_coverage'                   => 'ARC 적 병력 커버리지',
                'dungeonroute_view'                           => '던전 경로 보기',
                'dungeonroute_view_contents'                  => '경로 내용',
                'dungeonroute_mapping_version_usage'          => '매핑 버전 사용 현황',
                'enemyforces_import'                          => '적 병력 가져오기',
                'enemyforces_recalculate'                     => '적 병력 재계산',
                'features_list'                               => '기능',
                'mdt_dungeon_mapping_hash'                    => '던전 매핑 해시',
                'mdt_dungeon_mapping_version_accuracy'        => '던전 매핑 버전 정확도',
                'mdt_dungeon_mapping_version_to_mdt_mapping'  => '던전 매핑 버전과 MDT 매핑 간의 연결',
                'mdt_dungeonroute'                            => 'MDT 던전 경로',
                'mdt_list'                                    => 'MDT 문자열 목록',
                'messagebanner_set'                           => '메시지 배너 설정',
                'npc_manage_spell_visibility'                 => 'NPC 주문 표시 여부 관리',
                'wagogg_import_ingame_coordinates'            => '게임 내 좌표 가져오기',
                'artisancommands_backfill_kill_zone_enemy_id' => '풀의 적 ID 소급 채우기',
            ],
            'expansions' => [
                'expansions'     => '확장',
                'new_expansion'  => '새 확장',
                'edit_expansion' => '확장 편집',
            ],
            'dungeons' => [
                'dungeons'     => '던전',
                'new_dungeon'  => '새 던전',
                'edit_dungeon' => ':dungeon 편집',
            ],
            'floors' => [
                'new_floor'  => '새 층',
                'edit_floor' => '층 편집',
            ],
            'dungeonspeedrunrequirednpc' => [
                'new_dungeonspeedrunrequirednpc' => '새 :difficulty 던전 스피드런 필수 NPC',
            ],
            'npcs' => [
                'npcs'     => 'NPC',
                'new_npc'  => '새 NPC',
                'edit_npc' => ':npc 편집',
            ],
            'npcenemyforces' => [
                'new_npc_enemy_forces'  => '새 NPC 적군',
                'edit_npc_enemy_forces' => 'NPC 적군 편집',
            ],
            'npchealth' => [
                'new_npc_health'  => '새 NPC 체력',
                'edit_npc_health' => 'NPC 체력 편집',
            ],
            'spells' => [
                'spells'     => '주문',
                'new_spell'  => '새 주문',
                'edit_spell' => '주문 편집',
            ],
            'users' => [
                'users' => '사용자',
            ],
            'user_reports' => [
                'user_reports' => '사용자 보고서',
            ],
        ],
    ],

];
