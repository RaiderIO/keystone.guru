<?php

return [

    'admintools' => [
        'error' => [
            'mdt_string_parsing_failed'           => 'MDT 字串解析失敗。你確定貼上的是 MDT 字串嗎？',
            'mdt_string_format_not_recognized'    => '無法識別 MDT 字串格式。',
            'cli_weakauras_parser_not_found'      => 'cli_weakauras_parser 未安裝。',
            'invalid_mdt_string'                  => '無效的 MDT 字串',
            'invalid_mdt_string_exception'        => '無效的 MDT 字串：%s',
            'mdt_importer_not_configured'         => 'MDT 匯入器未正確配置。請聯繫管理員處理此問題。',
            'mdt_unable_to_find_npc_for_id'       => '無法找到 ID 為 %d 的 NPC',
            'mdt_mismatched_health'               => 'NPC %s 的生命值不匹配，MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces'         => 'NPC %s 的敵人部隊不匹配，MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces_teeming' => 'NPC %s 的敵人部隊繁盛不匹配，MDT: %s, KG: %s',
            'mdt_mismatched_enemy_count'          => 'NPC %s 的敵人數量不匹配，MDT: %s, KG: %s',
            'mdt_mismatched_enemy_type'           => 'NPC %s 的敵人類型不匹配，MDT: %s, KG: %s',
            'mdt_invalid_category'                => '無效的類別',
        ],
        'flash' => [
            'message_banner_set_successfully'        => '消息橫幅設置成功',
            'thumbnail_regenerate_result'            => '為 :total 路線派發了 :success 項任務。 :failed 失敗。',
            'combatlog_route_regenerate_result'      => '',
            'combatlog_criteria_reset'               => '',
            'combatlog_criteria_thresholds_updated'  => '',
            'caches_dropped_successfully'            => '快取成功清除',
            'releases_exported'                      => '版本已匯出',
            'exception'                              => '管理面板中拋出了例外',
            'feature_toggle_activated'               => '功能 :feature 現已啟動',
            'feature_toggle_deactivated'             => '功能 :feature 現已停用',
            'feature_forgotten'                      => '功能 :feature 成功被忘記',
            'mapping_version_upgrade_queued'         => '',
            'mapping_version_upgrade_already_latest' => '',
            'read_only_mode_disabled'                => '唯讀模式已停用',
            'read_only_mode_enabled'                 => '唯讀模式已啟用',
        ],
    ],
    'apidungeonroute' => [
        'mdt_generate_error'  => '生成您的 MDT 字串時出錯：%s',
        'mdt_generate_no_lua' => 'MDT 匯入器未正確配置。請聯繫管理員處理此問題',
    ],
    'apiuserreport' => [
        'error' => [
            'unable_to_update_user_report' => '無法更新用戶報告',
            'unable_to_save_report'        => '無法保存報告',
        ],
    ],
    'brushline' => [
        'error' => [
            'unable_to_save_brushline'   => '無法保存線條',
            'unable_to_delete_brushline' => '無法刪除線條',
        ],
    ],
    'arrow' => [
        'error' => [
            'unable_to_save_arrow'   => '',
            'unable_to_delete_arrow' => '',
        ],
    ],
    'dungeon' => [
        'flash' => [
            'dungeon_created' => '地下城已創建',
            'dungeon_updated' => '地下城已更新',
        ],
    ],
    'dungeonroute' => [
        'unable_to_save' => '無法保存路線',
        'flash'          => [
            'route_cloned_successfully' => '路線成功複製',
            'route_updated'             => '路線已更新',
            'route_created'             => '路線已創建',
        ],
    ],
    'dungeonroutediscover' => [
        'popular'           => '受歡迎的路線',
        'this_week_affixes' => '本週的詞綴',
        'next_week_affixes' => '下週的詞綴',
        'new'               => '新的',
        'season'            => [
            'popular'           => '%s 受歡迎的路線',
            'this_week_affixes' => '%s 本週',
            'next_week_affixes' => '%s 下週',
            'new'               => '%s 新的路線',
        ],
        'dungeon' => [
            'popular'           => '%s 受歡迎的路線',
            'this_week_affixes' => '%s 本週',
            'next_week_affixes' => '%s 下週',
            'new'               => '%s 新路線',
        ],
    ],
    'dungeonspeedrunrequirednpcs' => [
        'no_linked_npc' => '沒有連結的 NPC',
        'flash'         => [
            'npc_added_successfully'   => '成功添加所需的速通 NPC',
            'npc_deleted_successfully' => '成功移除所需的速通 NPC',
        ],
    ],
    'expansion' => [
        'flash' => [
            'unable_to_save_expansion' => '無法保存擴展包',
            'expansion_updated'        => '擴展包已更新',
            'expansion_created'        => '擴展包已創建',
        ],
    ],
    'generic' => [
        'error' => [
            'floor_not_found_in_dungeon' => '樓層不屬於地牢',
            'not_found'                  => '未找到',
        ],
    ],
    'oauthlogin' => [
        'flash' => [
            'registered_successfully' => '註冊成功。享受網站吧！',
            'user_exists'             => '已有使用者使用用戶名 %s。您之前已經註冊過嗎？',
            'email_exists'            => '已有使用者使用電子郵件地址 %s。您之前已經註冊過嗎？',
            'permission_denied'       => '無法註冊 - 請求被拒絕。請再試一次。',
            'read_only_mode_enabled'  => '已啟用唯讀模式。您目前無法註冊。',
        ],
    ],
    'register' => [
        'flash' => [
            'registered_successfully' => '註冊成功。享受網站吧！',
        ],
        'legal_agreed_required' => '您必須同意我們的法律條款才能註冊。',
        'legal_agreed_accepted' => '您必須同意我們的法律條款才能註冊。',
    ],
    'release' => [
        'error' => [
            'unable_to_save_release' => '無法保存版本',
        ],
        'flash' => [
            'release_updated'  => '版本已更新',
            'release_created'  => '版本已創建',
            'github_exception' => '與 Github 通信時發生錯誤：:message',
        ],
    ],
    'mappingversion' => [
        'created_successfully'      => '成功添加新的映射版本！',
        'created_bare_successfully' => '成功添加新的裸映射版本！',
        'deleted_successfully'      => '成功刪除映射版本',
    ],
    'mdtimport' => [
        'unknown_dungeon' => '未知的地牢',
        'error'           => [
            'mdt_string_parsing_failed'             => 'MDT 字符串解析失敗。您確定粘貼的是 MDT 字符串嗎？',
            'mdt_string_format_not_recognized'      => '未識別 MDT 字符串格式。',
            'cli_weakauras_parser_not_found'        => '未安裝 cli_weakauras_parser。',
            'invalid_mdt_string_exception'          => '無效的 MDT 字符串：%s',
            'invalid_mdt_string'                    => '無效的 MDT 字符串',
            'mdt_importer_not_configured_properly'  => 'MDT 匯入器未正確配置。請聯繫管理員處理此問題。',
            'cannot_create_route_must_be_logged_in' => '您必須登入才能創建路線',
        ],
    ],
    'path' => [
        'error' => [
            'unable_to_save_path'   => '無法保存路徑',
            'unable_to_delete_path' => '無法刪除路徑',
        ],
    ],
    'patreon' => [
        'flash' => [
            'unlink_successful'       => '您的 Patreon 帳戶已成功取消連結。',
            'link_successful'         => '您的 Patreon 已成功連結。謝謝！',
            'patreon_session_expired' => '您的 Patreon 會話已過期。請再試一次。',
            'session_expired'         => '您的會話已過期。請再試一次。',
            'patreon_error_occurred'  => 'Patreon 端發生錯誤。請稍後再試。',
            'internal_error_occurred' => '處理 Patreon 回應時發生錯誤 - 它似乎格式不正確。錯誤已被記錄，將會處理。請稍後再試。',
        ],
    ],
    'profile' => [
        'flash' => [
            'email_already_in_use'             => '該用戶名已被使用。',
            'username_already_in_use'          => '該用戶名已被使用。',
            'profile_updated'                  => '個人資料已更新',
            'unexpected_error_when_saving'     => '嘗試保存個人資料時發生意外錯誤',
            'privacy_settings_updated'         => '隱私設置已更新',
            'password_changed'                 => '密碼已更改',
            'new_password_equals_old_password' => '新密碼與舊密碼相同',
            'new_passwords_do_not_match'       => '新密碼不匹配',
            'current_password_is_incorrect'    => '當前密碼不正確',
            'tag_created_successfully'         => '標籤創建成功',
            'tag_already_exists'               => '此標籤已存在',
            'admins_cannot_delete_themselves'  => '管理員不能刪除自己！',
            'account_deleted_successfully'     => '帳戶已成功刪除。',
            'error_deleting_account'           => '發生錯誤。請再試一次。',
        ],
        'error' => [
            'add_ad_free_giveaway_limit_reached'        => '',
            'add_ad_free_giveaway_already_ad_free'      => '',
            'add_ad_free_giveaway_already_has_giveaway' => '',
            'remove_ad_free_giveaway_not_found'         => '',
            'remove_ad_free_giveaway_not_yours'         => '',
        ],
    ],
    'spell' => [
        'error' => [
            'unable_to_save_spell' => '無法保存法術',
        ],
        'flash' => [
            'spell_updated' => '法術已更新',
            'spell_created' => '法術已創建',
        ],
    ],
    'team' => [
        'flash' => [
            'team_updated'                        => '團隊已更新',
            'team_created'                        => '團隊已創建',
            'unable_to_find_team_for_invite_code' => '無法找到與此邀請碼相關的團隊',
            'invite_accept_success'               => '成功！您現在是團隊 %s 的成員。',
            'tag_created_successfully'            => '標籤創建成功',
            'tag_already_exists'                  => '此標籤已存在',
        ],
    ],
    'user' => [
        'flash' => [
            'user_is_now_an_admin'              => '用戶 :user 現在是管理員',
            'user_is_no_longer_an_admin'        => '用戶 :user 不再是管理員',
            'user_is_now_a_user'                => '用戶 :user 現在是用戶',
            'user_is_now_a_role'                => '用戶 :user 現在是 :role',
            'account_deleted_successfully'      => '帳戶已成功刪除。',
            'account_deletion_error'            => '發生錯誤。請再試一次。',
            'user_is_not_a_patron'              => '該用戶不是贊助者。',
            'all_benefits_granted_successfully' => '所有福利已成功授予。',
            'error_granting_all_benefits'       => '嘗試授予所有福利時發生錯誤。',
        ],
    ],

    'admin' => [
        'dungeonroute' => [
            'flash' => [
                'updated' => '',
                'deleted' => '',
                'claimed' => '',
            ],
        ],
    ],

];
