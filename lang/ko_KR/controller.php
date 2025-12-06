<?php

return [

    'admintools' => [
        'error' => [
            'mdt_string_parsing_failed'           => 'MDT 문자열 구문 분석에 실패했습니다. 정말로 MDT 문자열을 붙여넣었나요?',
            'mdt_string_format_not_recognized'    => 'MDT 문자열 형식이 인식되지 않았습니다.',
            'cli_weakauras_parser_not_found'      => 'cli_weakauras_parser가 설치되지 않았습니다.',
            'invalid_mdt_string'                  => '잘못된 MDT 문자열',
            'invalid_mdt_string_exception'        => '잘못된 MDT 문자열: %s',
            'mdt_importer_not_configured'         => 'MDT 가져오기가 제대로 구성되지 않았습니다. 이 문제에 대해 관리자에게 문의하십시오.',
            'mdt_unable_to_find_npc_for_id'       => 'ID %d에 대한 NPC를 찾을 수 없습니다',
            'mdt_mismatched_health'               => 'NPC %s의 체력 값이 불일치합니다, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces'         => 'NPC %s의 적 병력이 불일치합니다, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces_teeming' => 'NPC %s의 적 병력이 불일치합니다, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_count'          => 'NPC %s의 적 수가 불일치합니다, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_type'           => 'NPC %s의 적 유형이 불일치합니다, MDT: %s, KG: %s',
            'mdt_invalid_category'                => '잘못된 카테고리',
        ],
        'flash' => [
            'message_banner_set_successfully' => '메시지 배너가 성공적으로 설정되었습니다',
            'thumbnail_regenerate_result'     => ':total 경로를 위한 :success 작업이 배포되었습니다. :failed 실패했습니다.',
            'caches_dropped_successfully'     => '캐시가 성공적으로 삭제되었습니다',
            'releases_exported'               => '릴리스가 내보내졌습니다',
            'exception'                       => '관리자 패널에서 예외 발생',
            'feature_toggle_activated'        => '기능 :feature이(가) 이제 활성화되었습니다',
            'feature_toggle_deactivated'      => '기능 :feature이(가) 이제 비활성화되었습니다',
            'feature_forgotten'               => '기능 :feature이(가) 성공적으로 잊혀졌습니다',
            'read_only_mode_disabled'         => '읽기 전용 모드 비활성화됨',
            'read_only_mode_enabled'          => '읽기 전용 모드 활성화됨',
        ],
    ],
    'apidungeonroute' => [
        'mdt_generate_error'  => 'MDT 문자열 생성 중 오류가 발생했습니다: %s',
        'mdt_generate_no_lua' => 'MDT 가져오기가 제대로 구성되지 않았습니다. 이 문제에 대해 관리자에게 문의하십시오',
    ],
    'apiuserreport' => [
        'error' => [
            'unable_to_update_user_report' => '사용자 보고서를 업데이트할 수 없습니다',
            'unable_to_save_report'        => '보고서를 저장할 수 없습니다',
        ],
    ],
    'brushline' => [
        'error' => [
            'unable_to_save_brushline'   => '선을 저장할 수 없습니다',
            'unable_to_delete_brushline' => '선을 삭제할 수 없습니다',
        ],
    ],
    'dungeon' => [
        'flash' => [
            'dungeon_created' => '던전 생성',
            'dungeon_updated' => '던전 업데이트됨',
        ],
    ],
    'dungeonroute' => [
        'unable_to_save' => '경로를 저장할 수 없습니다',
        'flash'          => [
            'route_cloned_successfully' => '경로가 성공적으로 복제되었습니다',
            'route_updated'             => '경로 업데이트됨',
            'route_created'             => '경로 생성됨',
        ],
    ],
    'dungeonroutediscover' => [
        'popular'           => '인기 경로',
        'this_week_affixes' => '이번 주 속성',
        'next_week_affixes' => '다음 주 속성',
        'new'               => '새로운',
        'season'            => [
            'popular'           => '%s 인기 경로',
            'this_week_affixes' => '%s 이번 주',
            'next_week_affixes' => '%s 다음 주',
            'new'               => '%s개의 새로운 경로',
        ],
        'dungeon' => [
            'popular'           => '%s 인기 있는 경로',
            'this_week_affixes' => '이번 주 %s',
            'next_week_affixes' => '다음 주 %s',
            'new'               => '%s 새로운 경로',
        ],
    ],
    'dungeonspeedrunrequirednpcs' => [
        'no_linked_npc' => '연결된 NPC 없음',
        'flash'         => [
            'npc_added_successfully'   => 'NPC가 성공적으로 추가되었습니다',
            'npc_deleted_successfully' => 'NPC가 성공적으로 제거되었습니다',
        ],
    ],
    'expansion' => [
        'flash' => [
            'unable_to_save_expansion' => '확장팩을 저장할 수 없습니다',
            'expansion_updated'        => '확장팩이 업데이트되었습니다',
            'expansion_created'        => '확장팩이 생성되었습니다',
        ],
    ],
    'generic' => [
        'error' => [
            'floor_not_found_in_dungeon' => '던전에 속하지 않는 층',
            'not_found'                  => '찾을 수 없음',
        ],
    ],
    'oauthlogin' => [
        'flash' => [
            'registered_successfully' => '성공적으로 등록되었습니다. 웹사이트를 즐기세요!',
            'user_exists'             => '사용자 이름 %s를 가진 사용자가 이미 있습니다. 이미 등록하셨습니까?',
            'email_exists'            => '이메일 주소 %s를 가진 사용자가 이미 있습니다. 이미 등록하셨습니까?',
            'permission_denied'       => '등록할 수 없습니다 - 요청이 거부되었습니다. 다시 시도하십시오.',
            'read_only_mode_enabled'  => '읽기 전용 모드가 활성화되어 있습니다. 현재 등록할 수 없습니다.',
        ],
    ],
    'register' => [
        'flash' => [
            'registered_successfully' => '성공적으로 등록되었습니다. 웹사이트를 즐기세요!',
        ],
        'legal_agreed_required' => '등록하려면 법적 약관에 동의해야 합니다.',
        'legal_agreed_accepted' => '등록하려면 법적 약관에 동의해야 합니다.',
    ],
    'release' => [
        'error' => [
            'unable_to_save_release' => '릴리스를 저장할 수 없습니다',
        ],
        'flash' => [
            'release_updated'  => '릴리스가 업데이트되었습니다',
            'release_created'  => '릴리스가 생성되었습니다',
            'github_exception' => 'Github와 통신하는 동안 오류가 발생했습니다: :message',
        ],
    ],
    'mappingversion' => [
        'created_successfully'      => '새로운 매핑 버전이 추가되었습니다!',
        'created_bare_successfully' => '새로운 기본 매핑 버전이 추가되었습니다!',
        'deleted_successfully'      => '매핑 버전이 성공적으로 삭제되었습니다',
    ],
    'mdtimport' => [
        'unknown_dungeon' => '알 수 없는 던전',
        'error'           => [
            'mdt_string_parsing_failed'             => 'MDT 문자열 구문 분석에 실패했습니다. 정말로 MDT 문자열을 붙여넣으셨습니까?',
            'mdt_string_format_not_recognized'      => 'MDT 문자열 형식을 인식할 수 없습니다.',
            'cli_weakauras_parser_not_found'        => 'cli_weakauras_parser가 설치되지 않았습니다.',
            'invalid_mdt_string_exception'          => '잘못된 MDT 문자열: %s',
            'invalid_mdt_string'                    => '잘못된 MDT 문자열',
            'mdt_importer_not_configured_properly'  => 'MDT 가져오기 기능이 올바르게 구성되지 않았습니다. 이 문제에 대해 관리자에게 문의하십시오.',
            'cannot_create_route_must_be_logged_in' => '경로를 생성하려면 로그인해야 합니다',
        ],
    ],
    'path' => [
        'error' => [
            'unable_to_save_path'   => '경로를 저장할 수 없습니다',
            'unable_to_delete_path' => '경로를 삭제할 수 없습니다',
        ],
    ],
    'patreon' => [
        'flash' => [
            'unlink_successful'       => 'Patreon 계정이 성공적으로 연결 해제되었습니다.',
            'link_successful'         => 'Patreon이 성공적으로 연결되었습니다. 감사합니다!',
            'patreon_session_expired' => 'Patreon 세션이 만료되었습니다. 다시 시도하십시오.',
            'session_expired'         => '세션이 만료되었습니다. 다시 시도하십시오.',
            'patreon_error_occurred'  => 'Patreon 측에서 오류가 발생했습니다. 나중에 다시 시도하십시오.',
            'internal_error_occurred' => 'Patreon의 응답을 처리하는 동안 오류가 발생했습니다 - 응답이 잘못된 것 같습니다. 오류가 기록되었으며 처리될 것입니다. 나중에 다시 시도하십시오.',
        ],
    ],
    'profile' => [
        'flash' => [
            'email_already_in_use'             => '해당 사용자 이름은 이미 사용 중입니다.',
            'username_already_in_use'          => '해당 사용자 이름은 이미 사용 중입니다.',
            'profile_updated'                  => '프로필이 업데이트되었습니다',
            'unexpected_error_when_saving'     => '프로필을 저장하는 동안 예상치 못한 오류가 발생했습니다',
            'privacy_settings_updated'         => '개인정보 설정이 업데이트되었습니다',
            'password_changed'                 => '비밀번호가 변경되었습니다',
            'new_password_equals_old_password' => '새 비밀번호가 이전 비밀번호와 같습니다',
            'new_passwords_do_not_match'       => '새 비밀번호가 일치하지 않습니다',
            'current_password_is_incorrect'    => '현재 비밀번호가 올바르지 않습니다',
            'tag_created_successfully'         => '태그가 성공적으로 생성되었습니다',
            'tag_already_exists'               => '이 태그는 이미 존재합니다',
            'admins_cannot_delete_themselves'  => '관리자는 자신을 삭제할 수 없습니다!',
            'account_deleted_successfully'     => '계정이 성공적으로 삭제되었습니다.',
            'error_deleting_account'           => '오류가 발생했습니다. 다시 시도하십시오.',
        ],
    ],
    'spell' => [
        'error' => [
            'unable_to_save_spell' => '주문을 저장할 수 없습니다',
        ],
        'flash' => [
            'spell_updated' => '주문이 업데이트되었습니다',
            'spell_created' => '주문이 생성되었습니다',
        ],
    ],
    'team' => [
        'flash' => [
            'team_updated'                        => '팀이 업데이트되었습니다',
            'team_created'                        => '팀이 생성되었습니다',
            'unable_to_find_team_for_invite_code' => '이 초대 코드와 관련된 팀을 찾을 수 없습니다',
            'invite_accept_success'               => '성공! 이제 팀 %s의 멤버입니다.',
            'tag_created_successfully'            => '태그가 성공적으로 생성되었습니다',
            'tag_already_exists'                  => '이 태그는 이미 존재합니다',
        ],
    ],
    'user' => [
        'flash' => [
            'user_is_now_an_admin'              => '사용자 :user는 이제 관리자입니다.',
            'user_is_no_longer_an_admin'        => '사용자 :user는 더 이상 관리자가 아닙니다',
            'user_is_now_a_user'                => '사용자 :user는 이제 사용자입니다.',
            'user_is_now_a_role'                => '사용자 :user는 이제 :role입니다.',
            'account_deleted_successfully'      => '계정이 성공적으로 삭제되었습니다.',
            'account_deletion_error'            => '오류가 발생했습니다. 다시 시도하십시오.',
            'user_is_not_a_patron'              => '이 사용자는 후원자가 아닙니다.',
            'all_benefits_granted_successfully' => '모든 혜택이 성공적으로 부여되었습니다.',
            'error_granting_all_benefits'       => '모든 혜택을 부여하는 동안 오류가 발생했습니다.',
        ],
    ],

];
