<?php

return [

    'mdt' => [
        'io' => [
            'export_string' => [
                'category' => [
                    'pull'     => '풀 %d',
                    'title'    => '제목',
                    'map_icon' => '지도 아이콘',
                ],
                'unable_to_find_mdt_enemy_for_kg_enemy'             => 'NPC %s (enemy_id: %d, npc_id: %d)와 일치하는 Keystone.guru 적에 대한 MDT 등가물을 찾을 수 없습니다.',
                'unable_to_find_mdt_enemy_for_kg_enemy_details'     => '이것은 귀하의 경로가 MDT에 알려진 NPC의 적을 처치하지만 Keystone.guru가 아직 그 적을 MDT 등가물과 연결하지 않았거나 MDT에 존재하지 않음을 나타냅니다.',
                'unable_to_find_mdt_enemy_for_kg_caused_empty_pull' => '선택한 모든 적을 MDT에서 찾을 수 없어 비어 있는 풀로 인해 이 풀은 삭제되었습니다.',
                'route_title_contains_non_ascii_char_bug'           => '경로 제목에 아직 해결되지 않은 Keystone.guru의 인코딩 버그를 유발하는 것으로 알려진 비 ASCII 문자가 포함되어 있습니다.
                                                        경로 제목에서 문제가 되는 모든 문자가 제거되었습니다. 불편을 끼쳐드려 죄송하며 이 문제를 조속히 해결할 수 있기를 바랍니다.',
                'route_title_contains_non_ascii_char_bug_details' => '기존 제목: %s, 새로운 제목: %s',
                'map_icon_contains_non_ascii_char_bug'            => '지도 아이콘에 대한 주석 중 하나에 아직 해결되지 않은 Keystone.guru의 인코딩 버그를 유발하는 것으로 알려진 비 ASCII 문자가 포함되어 있습니다. 귀하의 지도 주석에서 문제가 되는 모든 문자가 제거되었습니다. 불편을 끼쳐드려 죄송하며 이 문제를 조속히 해결할 수 있기를 바랍니다.',
                'map_icon_contains_non_ascii_char_bug_details'    => '기존 주석: "%s", 새로운 주석: "%s"',
            ],
            'import_string' => [
                'category' => [
                    'awakened_obelisks' => '각성된 오벨리스크',
                    'pulls'             => '풀',
                    'notes'             => '노트',
                    'pull'              => '풀 %d',
                    'object'            => '객체 %d',
                ],
                'object_out_of_bounds'                                 => '주석을 추가할 수 없음: ":comment" 객체가 범위를 벗어났습니다.',
                'limit_reached_pulls'                                  => '경로를 가져올 수 없음: :limit 풀을 초과함.',
                'limit_reached_brushlines'                             => '경로를 가져올 수 없음: :limit 라인을 초과함.',
                'limit_reached_paths'                                  => '경로를 가져올 수 없음: :limit 경로를 초과함.',
                'limit_reached_notes'                                  => '경로를 가져올 수 없음: :limit 노트를 초과함.',
                'unable_to_find_floor_for_object'                      => 'MDT 층 ID %d와 일치하는 Keystone.guru 층을 찾을 수 없습니다.',
                'unable_to_find_floor_for_object_details'              => '이것은 MDT에 Keystone.guru가 가지고 있지 않은 층이 있음을 나타냅니다.',
                'unable_to_find_mdt_enemy_for_clone_index'             => '클론 인덱스 %s 및 npc 인덱스 %s에 대한 MDT 적을 찾을 수 없습니다.',
                'unable_to_find_mdt_enemy_for_clone_index_details'     => '이것은 MDT가 아직 Keystone.guru에 알려지지 않은 적을 매핑했음을 나타냅니다.',
                'unable_to_find_kg_equivalent_for_mdt_enemy'           => 'NPC %s (id: %s)를 가진 MDT 적 %s에 대한 Keystone.guru 등가물을 찾을 수 없습니다.',
                'unable_to_find_kg_equivalent_for_mdt_enemy_details'   => '이것은 귀하의 경로가 Keystone.guru에 알려진 NPC가 있는 적을 처치하지만, Keystone.guru에는 아직 해당 적이 매핑되지 않았음을 나타냅니다.',
                'unable_to_find_awakened_enemy_for_final_boss'         => '최종 보스에서 %s (%s)에 대한 각성된 적을 찾을 수 없습니다.',
                'unable_to_find_awakened_enemy_for_final_boss_details' => '이것은 Keystone.guru에 수정이 필요한 매핑 오류가 있음을 나타냅니다. 위의 경고를 저에게 보내주시면 수정하겠습니다.',
                'unable_to_find_enemies_pull_skipped'                  => '적을 찾지 못해 풀링이 건너뛰어졌습니다.',
                'unable_to_find_enemies_pull_skipped_details'          => '이것은 MDT가 최근 업데이트되었지만 아직 Keystone.guru에 통합되지 않았음을 나타낼 수 있습니다.',
                'unable_to_find_awakened_obelisks'                     => '던전/주 조합에 대한 Awakened Obelisks를 찾을 수 없습니다. Awakened Obelisk 건너뛰기가 가져오지 않습니다.',
                'unable_to_find_awakened_obelisk_different_floor'      => 'Awakened Obelisk :name을 가져올 수 없습니다. 이는 Obelisk 자체와 다른 층에 있습니다. 현재 Keystone.guru는 이를 지원하지 않습니다.',
                'unable_to_decode_mdt_import_string'                   => 'MDT 가져오기 문자열을 디코딩할 수 없음',
                'unable_to_validate_mdt_import_string'                 => 'MDT 가져오기 문자열을 검증할 수 없습니다',
            ],
        ],
    ],
    'npcservice' => [
        'all_dungeons' => '모든 던전',
    ],
    'combatlogservice' => [
        'analyze_combat_log' => [
            'verify_error'     => '전투 로그를 확인할 수 없음: 오류.',
            'processing_error' => '전투 로그를 처리할 수 없음: 오류.',
        ],
    ],

];
