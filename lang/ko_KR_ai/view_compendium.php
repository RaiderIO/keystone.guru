<?php

return [
    'index' => [
        'title'       => '도감',
        'header'      => '도감',
        'intro'       => '도감은 현재 시즌의 모든 던전을 다루는 커뮤니티 기반 백과사전입니다. 각 NPC가 정확히 무엇을 하는지, 어떤 주문을 시전하는지, 어떻게 대응해야 하는지, 어떤 군중 제어가 통하는지 확인해 보세요.',
        'data_source' => [
            'title'       => '항상 최신 상태',
            'description' => '도감은 플레이어가 Raider.IO 클라이언트를 통해 자동으로 업로드하는 전투 기록으로 실시간 갱신됩니다. 추적된 모든 런은 조용히 모두를 위한 데이터를 개선합니다.',
            'cta'         => 'Raider.IO 클라이언트 설치하기',
        ],
        'how_it_works' => [
            'title'  => '이용 방법',
            'step_1' => [
                'title'       => '섹션 선택',
                'description' => 'NPC, 주문, 최근 활동을 둘러보거나 직업별 군중 제어로 바로 이동하세요.',
            ],
            'step_2' => [
                'title'       => '검색 및 필터',
                'description' => '던전으로 필터링하여 준비 중인 풀에만 집중하세요.',
            ],
            'step_3' => [
                'title'       => '세부 정보 확인',
                'description' => 'NPC나 주문을 열어 속성, 정화 유형, 메커닉, 지속 시간 등을 확인하세요.',
            ],
        ],
        'cards' => [
            'npc' => [
                'title'        => 'NPC',
                'description'  => '게임 내 모든 NPC의 능력, 체력, 분류 및 등장하는 던전을 확인하세요.',
                'cta'          => 'NPC 둘러보기',
                'count_suffix' => '개의 NPC 수록됨',
            ],
            'spell' => [
                'title'        => '주문',
                'description'  => '주문을 검색하여 어떤 효과가 있는지, 어떻게 피할 수 있는지, 어떤 NPC가 시전하는지 확인하세요.',
                'cta'          => '주문 둘러보기',
                'count_suffix' => '개의 주문 수록됨',
            ],
            'activity' => [
                'title'       => '활동',
                'description' => '커뮤니티에서 유입되는 최신 데이터를 날짜별로 정리한 실시간 피드입니다.',
                'cta'         => '활동 보기',
                'subtitle'    => '매일 업데이트',
            ],
            'class' => [
                'title'        => '직업별',
                'description'  => '자신의 군중 제어 주문이 어떤 NPC에게 통하는지 직업별로 확인하세요.',
                'cta'          => '직업별로 둘러보기',
                'count_suffix' => '개 직업 포함',
            ],
        ],
    ],
    'event' => [
        'characteristic_added'    => ':name의 영향을 받음',
        'characteristic_removed'  => ':name의 영향을 받지 않음',
        'spell_assigned'          => ':name 시전',
        'spell_created'           => ':spell이(가) 데이터베이스에 추가됨',
        'property_changed'        => ':property의 영향을 받음',
        'property_removed'        => ':property의 영향을 받지 않음',
        'counter_added'           => '이제 :spell을(를) :property(으)로 카운터할 수 있음',
        'counter_removed'         => '더 이상 :spell을(를) :property(으)로 카운터할 수 없음',
        'school_recorded'         => ':spell이(가) :schools 피해를 입힘',
        'immunity_bypass_added'   => ':spell이(가) :property을(를) 통해 적중하는 것이 관측됨',
        'immunity_bypass_removed' => ':spell이(가) 더 이상 :property을(를) 통해 적중하는 것이 관측되지 않음',
        // Subject-less variants: used when the row already leads with the spell link as its
        // subject, so the description does not repeat the spell name
        'spell_created_no_subject'           => '데이터베이스에 추가됨',
        'counter_added_no_subject'           => '이제 :property(으)로 카운터할 수 있음',
        'counter_removed_no_subject'         => '더 이상 :property(으)로 카운터할 수 없음',
        'school_recorded_no_subject'         => ':schools 피해를 입힘',
        'immunity_bypass_added_no_subject'   => ':property을(를) 통해 적중하는 것이 관측됨',
        'immunity_bypass_removed_no_subject' => '더 이상 :property을(를) 통해 적중하는 것이 관측되지 않음',
        'count'                              => ':count개 이벤트|:count개 이벤트',
        'more'                               => '외 :count개 더보기',
        'property'                           => [
            'aura'   => '오라',
            'debuff' => '디버프',
        ],
    ],
    'npc' => [
        'index' => [
            'title'                 => 'NPC 도감',
            'header'                => 'NPC 도감',
            'boss'                  => '보스',
            'table_header_name'     => '이름',
            'table_header_dungeons' => '던전',
            'table_header_spells'   => '주문',
        ],
        'show' => [
            'title'   => ':name - NPC 도감',
            'wowhead' => 'Wowhead에서 보기',
        ],
        'sections' => [
            'header' => [
                'level' => '레벨',
            ],
            'characteristics' => [
                'title'        => '특성',
                'tooltip'      => '이 NPC는 무엇의 영향을 받나요?',
                'empty'        => '기록된 특성이 없습니다.',
                'not_observed' => '관측되지 않음:',
            ],
            'spells' => [
                'title'                              => '주문',
                'empty'                              => '기록된 주문이 없습니다.',
                'header_name'                        => '이름',
                'header_schools'                     => '속성',
                'header_schools_tooltip'             => '이 주문은 어떤 유형의 피해를 입히나요?',
                'header_miss_types'                  => '빗나감 유형',
                'header_miss_types_tooltip'          => '이 주문을 피하려면 어떻게 해야 하나요?',
                'header_counters'                    => '카운터',
                'header_counters_tooltip'            => '이 주문을 무산시키거나 대상을 변경할 수 있는 플레이어 능력입니다.',
                'header_bypasses_immunities'         => '면역 무시',
                'header_bypasses_immunities_tooltip' => '이 주문을 막지 못하는 플레이어 면역 - 해당 면역이 활성화된 상태에서도 적중하는 것이 관측되었습니다.',
                'header_dispel_type'                 => '정화 유형',
                'header_dispel_type_tooltip'         => '이 주문을 해제하려면 어떤 정화 유형을 사용해야 하나요?',
                'header_mechanic'                    => '메커닉',
                'header_cast_time'                   => '시전 시간',
                'header_duration'                    => '지속 시간',
            ],
            'event_feed' => [
                'title' => '최근 활동',
                'empty' => '아직 기록된 활동이 없습니다.',
            ],
        ],
    ],
    'spell' => [
        'index' => [
            'title'                 => '주문 도감',
            'header'                => '주문 도감',
            'table_header_name'     => '이름',
            'table_header_dungeons' => '던전',
            'table_header_used_by'  => '사용하는 NPC',
        ],
        'show' => [
            'title'   => ':name - 주문 도감',
            'wowhead' => 'Wowhead에서 보기',
        ],
        'sections' => [
            'header' => [
                'aura'   => '오라',
                'debuff' => '디버프',
            ],
            'description' => [
                'title' => '설명',
            ],
            'details' => [
                'title'                              => '상세 정보',
                'header_schools'                     => '속성',
                'header_schools_tooltip'             => '이 주문은 어떤 유형의 피해를 입히나요?',
                'header_miss_types'                  => '빗나감 유형',
                'header_miss_types_tooltip'          => '이 주문을 피하려면 어떻게 해야 하나요?',
                'header_counters'                    => '카운터',
                'header_counters_tooltip'            => '이 주문을 무산시키거나 대상을 변경할 수 있는 플레이어 능력입니다.',
                'header_bypasses_immunities'         => '면역 무시',
                'header_bypasses_immunities_tooltip' => '이 주문을 막지 못하는 플레이어 면역 - 해당 면역이 활성화된 상태에서도 적중하는 것이 관측되었습니다.',
                'header_dispel_type'                 => '정화 유형',
                'header_dispel_type_tooltip'         => '이 주문을 해제하려면 어떤 정화 유형을 사용해야 하나요?',
                'header_mechanic'                    => '메커닉',
                'header_cast_time'                   => '시전 시간',
                'header_duration'                    => '지속 시간',
            ],
            'dungeons' => [
                'title'       => '던전',
                'empty'       => '연결된 던전이 없습니다.',
                'header_name' => '이름',
            ],
            'npcs' => [
                'title'                 => '사용하는 NPC',
                'empty'                 => '기록된 NPC가 없습니다.',
                'header_name'           => '이름',
                'header_classification' => '분류',
                'header_dungeons'       => '던전',
            ],
            'event_feed' => [
                'title' => '최근 활동',
                'empty' => '아직 기록된 활동이 없습니다.',
            ],
        ],
    ],
    'activity' => [
        'index' => [
            'title'  => '도감 활동',
            'header' => '도감 활동',
            'empty'  => '아직 기록된 활동이 없습니다.',
        ],
        'day' => [
            'title'  => ':date - 도감 활동',
            'header' => ':date의 도감 활동',
            'empty'  => '이 날짜에 기록된 활동이 없습니다.',
        ],
    ],
    'class' => [
        'index' => [
            'title'  => '도감 - 직업별',
            'header' => '직업별',
        ],
        'show' => [
            'title'                       => ':name - 직업별',
            'table_header_spell'          => '주문',
            'table_header_characteristic' => '특성',
            'table_header_npcs'           => '주요 NPC',
            'no_spells'                   => '이 게임 버전에서 이 직업의 군중 제어 주문을 찾을 수 없습니다.',
            'no_npcs'                     => '-',
            'npcs_no_effect'              => '면역',
            'npcs_works_on'               => '효과 있음',
            'npcs_no_exceptions'          => '예상치 못한 사항 없음',
            'npcs_no_data'                => '데이터 없음',
            'npcs_description'            => '예상치 못한 경우만 나열됩니다 - 저항한 잡몹, 그리고 그럼에도 적중한 보스입니다. 이미 예상한 대로 동작하는 항목은 제외됩니다. "효과 관측 안 됨"은 이 표의 다른 군중 제어가 해당 NPC에게 적중하는 것이 관측된 적은 있지만 이 군중 제어는 그런 적이 없다는 뜻입니다: 이는 확인된 면역이 아니라 하나의 증거일 뿐입니다.',
            'counters'                    => [
                'title'              => '카운터 가능한 능력',
                'racial'             => '종족 특성 (:race)',
                'no_spells'          => '이 던전에서 카운터 가능한 NPC 주문을 찾을 수 없습니다.',
                'table_header_spell' => '주문',
                'table_header_npcs'  => 'NPC',
            ],
            'reflect' => [
                'title'              => '반사 가능한 주문',
                'description'        => '이 던전에서 반사되는 것이 관측된 NPC 주문입니다.',
                'no_spells'          => '이 던전에서 반사 가능한 NPC 주문을 찾을 수 없습니다.',
                'table_header_spell' => '주문',
                'table_header_npcs'  => 'NPC',
            ],
        ],
    ],
];
