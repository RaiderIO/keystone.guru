<?php

return [

    'admintools' => [
        'error' => [
            'mdt_string_parsing_failed'           => 'MDT 字符串解析失败。您是否真的粘贴了 MDT 字符串？',
            'mdt_string_format_not_recognized'    => '未识别的 MDT 字符串格式。',
            'cli_weakauras_parser_not_found'      => '未安装 cli_weakauras_parser。',
            'invalid_mdt_string'                  => '无效的 MDT 字符串',
            'invalid_mdt_string_exception'        => '无效的 MDT 字符串：%s',
            'mdt_importer_not_configured'         => 'MDT 导入器配置不正确。请联系管理员解决此问题。',
            'mdt_unable_to_find_npc_for_id'       => '无法找到 ID 为 %d 的 NPC',
            'mdt_mismatched_health'               => 'NPC %s 的生命值不匹配，MDT：%s，KG：%s',
            'mdt_mismatched_enemy_forces'         => 'NPC %s 的敌人力量不匹配，MDT：%s，KG：%s',
            'mdt_mismatched_enemy_forces_teeming' => 'NPC %s 的繁茂敌人力量不匹配，MDT：%s，KG：%s',
            'mdt_mismatched_enemy_count'          => 'NPC %s 的敌人数量不匹配，MDT：%s，KG：%s',
            'mdt_mismatched_enemy_type'           => 'NPC %s 的敌人类型不匹配，MDT：%s，KG：%s',
            'mdt_invalid_category'                => '无效的类别',
        ],
        'flash' => [
            'banned_ip_address_added'                => 'IP地址封禁成功',
            'banned_ip_address_removed'              => '封禁移除成功',
            'message_banner_set_successfully'        => '消息横幅设置成功',
            'thumbnail_regenerate_result'            => '为 :total 路线派发了 :success 个作业。:failed 失败。',
            'combatlog_route_regenerate_result'      => '已派发 :count 个任务',
            'combatlog_criteria_reset'               => '今日所有解析条件计数已重置。',
            'combatlog_criteria_thresholds_updated'  => '解析条件阈值已更新。',
            'caches_dropped_successfully'            => '缓存成功清除',
            'caches_drop_queued'                     => '缓存清除已加入队列，将在后台运行',
            'exception'                              => '管理员面板中抛出异常',
            'feature_toggle_activated'               => '功能 :feature 已激活',
            'feature_toggle_deactivated'             => '功能 :feature 已停用',
            'feature_forgotten'                      => '功能 :feature 成功遗忘',
            'mapping_version_upgrade_queued'         => '已将 :count 条路线加入队列，从 :version 升级到最新版本。',
            'mapping_version_upgrade_already_latest' => '此映射版本已是该地下城的最新版本——未加入任何路线队列。',
            'read_only_mode_disabled'                => '只读模式已禁用',
            'read_only_mode_enabled'                 => '只读模式已启用',
        ],
    ],
    'affix' => [
        'flash' => [
            'affix_created' => '词缀已创建',
            'affix_updated' => '词缀已更新',
        ],
    ],
    'affixgroup' => [
        'flash' => [
            'affixgroup_created' => '词缀组已创建',
            'affixgroup_updated' => '词缀组已更新',
            'affixgroup_deleted' => '词缀组已删除',
        ],
    ],
    'apicombatlogrun' => [
        'error' => [
            'no_segments' => '此次运行没有可用的战斗日志片段。',
        ],
    ],
    'apidungeonroute' => [
        'mdt_generate_error'  => '生成 MDT 字符串时发生错误：%s',
        'mdt_generate_no_lua' => 'MDT 导入器配置不正确。请联系管理员解决此问题',
    ],
    'apiuserreport' => [
        'error' => [
            'unable_to_update_user_report' => '无法更新用户报告',
            'unable_to_save_report'        => '无法保存报告',
        ],
    ],
    'brushline' => [
        'error' => [
            'unable_to_save_brushline'   => '无法保存线条',
            'unable_to_delete_brushline' => '无法删除线条',
        ],
    ],
    'arrow' => [
        'error' => [
            'unable_to_save_arrow'   => '无法保存箭头',
            'unable_to_delete_arrow' => '无法删除箭头',
        ],
    ],
    'dungeon' => [
        'flash' => [
            'dungeon_created' => '地下城已创建',
            'dungeon_updated' => '地下城已更新',
        ],
    ],
    'dungeonroute' => [
        'unable_to_save' => '无法保存路线',
        'flash'          => [
            'route_cloned_successfully' => '路线克隆成功',
            'route_updated'             => '路线已更新',
            'route_created'             => '路线已创建',
        ],
    ],
    'dungeonroutediscover' => [
        'popular' => '热门路线',
        'new'     => '新',
        'season'  => [
            'popular' => '%s 热门路线',
            'new'     => '%s 新路线',
        ],
        'dungeon' => [
            'popular' => '%s 受欢迎的路线',
            'new'     => '%s 条新路线',
        ],
    ],
    'dungeonspeedrunrequirednpcs' => [
        'no_linked_npc' => '没有关联的NPC',
        'flash'         => [
            'npc_added_successfully'   => '成功添加NPC',
            'npc_deleted_successfully' => '成功移除NPC',
        ],
    ],
    'expansion' => [
        'flash' => [
            'unable_to_save_expansion' => '无法保存扩展',
            'expansion_updated'        => '扩展已更新',
            'expansion_created'        => '扩展已创建',
        ],
    ],
    'generic' => [
        'error' => [
            'floor_not_found_in_dungeon' => '楼层不属于地下城',
            'not_found'                  => '未找到',
        ],
    ],
    'killzone' => [
        'error' => [
            'facade_location_not_convertible' => '无法在此处放置拉怪——该位置不属于此地下城的任何楼层',
        ],
    ],
    'oauthlogin' => [
        'flash' => [
            'registered_successfully' => '注册成功。享受网站吧！',
            'user_exists'             => '已有用户使用用户名%s。您之前注册过吗？',
            'email_exists'            => '已有用户使用电子邮件地址%s。您之前注册过吗？',
            'permission_denied'       => '无法注册 - 请求被拒绝。请再试一次。',
            'read_only_mode_enabled'  => '只读模式已启用。您当前无法注册。',
        ],
    ],
    'register' => [
        'flash' => [
            'registered_successfully' => '注册成功。享受网站吧！',
        ],
        'legal_agreed_required' => '您必须同意我们的法律条款才能注册。',
        'legal_agreed_accepted' => '您必须同意我们的法律条款才能注册。',
    ],
    'mappingversion' => [
        'created_successfully'      => '添加了新的映射版本！',
        'created_bare_successfully' => '添加了新的裸映射版本！',
        'deleted_successfully'      => '成功删除映射版本',
    ],
    'mdtimport' => [
        'unknown_dungeon' => '未知的地下城',
        'error'           => [
            'mdt_string_parsing_failed'             => 'MDT字符串解析失败。您真的粘贴了MDT字符串吗？',
            'mdt_string_format_not_recognized'      => '无法识别MDT字符串格式。',
            'cli_weakauras_parser_not_found'        => '未安装cli_weakauras_parser。',
            'invalid_mdt_string_exception'          => '无效的MDT字符串：%s',
            'invalid_mdt_string'                    => '无效的MDT字符串',
            'mdt_importer_not_configured_properly'  => 'MDT导入器配置不正确。请联系管理员解决此问题。',
            'cannot_create_route_must_be_logged_in' => '您必须登录才能创建路线',
        ],
    ],
    'path' => [
        'error' => [
            'unable_to_save_path'   => '无法保存路径',
            'unable_to_delete_path' => '无法删除路径',
        ],
    ],
    'patreon' => [
        'flash' => [
            'unlink_successful'       => '您的Patreon账户已成功取消链接。',
            'link_successful'         => '您的Patreon已成功链接。谢谢！',
            'patreon_session_expired' => '您的Patreon会话已过期。请再试一次。',
            'session_expired'         => '您的会话已过期。请再试一次。',
            'patreon_error_occurred'  => 'Patreon发生错误。请稍后再试。',
            'internal_error_occurred' => '处理Patreon响应时发生错误 - 它似乎格式不正确。错误已记录，将会被处理。请稍后再试。',
        ],
    ],
    'profile' => [
        'flash' => [
            'email_already_in_use'             => '该用户名已被使用。',
            'username_already_in_use'          => '该用户名已被使用。',
            'profile_updated'                  => '个人资料已更新',
            'unexpected_error_when_saving'     => '保存您的个人资料时发生意外错误',
            'privacy_settings_updated'         => '隐私设置已更新',
            'creator_profile_updated'          => '创作者资料已更新',
            'password_changed'                 => '密码已更改',
            'new_password_equals_old_password' => '新密码与旧密码相同',
            'new_passwords_do_not_match'       => '新密码不匹配',
            'current_password_is_incorrect'    => '当前密码不正确',
            'tag_created_successfully'         => '标签创建成功',
            'tag_already_exists'               => '该标签已存在',
            'admins_cannot_delete_themselves'  => '管理员不能删除自己！',
            'account_deleted_successfully'     => '账户删除成功。',
            'error_deleting_account'           => '发生错误。请再试一次。',
        ],
        'error' => [
            'add_ad_free_giveaway_limit_reached'        => '无法添加更多无广告赠品，已达到上限。',
            'add_ad_free_giveaway_already_ad_free'      => '无法添加无广告赠品，该用户已通过自己的 Patreon 订阅获得无广告状态。',
            'add_ad_free_giveaway_already_has_giveaway' => '无法添加无广告赠品，该用户已通过现有赠品获得无广告状态。',
            'remove_ad_free_giveaway_not_found'         => '无法移除无广告赠品——该用户目前没有任何无广告赠品。',
            'remove_ad_free_giveaway_not_yours'         => '无法移除并非由您赠送的无广告赠品。',
        ],
    ],
    'season' => [
        'flash' => [
            'season_created' => '赛季已创建',
            'season_updated' => '赛季已更新',
        ],
    ],
    'spell' => [
        'error' => [
            'unable_to_save_spell' => '无法保存法术',
        ],
        'flash' => [
            'spell_updated' => '法术已更新',
            'spell_created' => '法术已创建',
        ],
    ],
    'team' => [
        'flash' => [
            'team_updated'                        => '团队已更新',
            'team_created'                        => '团队已创建',
            'unable_to_find_team_for_invite_code' => '无法找到与此邀请代码关联的团队',
            'invite_accept_success'               => '成功！您现在是团队%s的成员。',
            'tag_created_successfully'            => '标签创建成功',
            'tag_already_exists'                  => '该标签已存在',
        ],
    ],
    'user' => [
        'flash' => [
            'user_is_now_an_admin'              => '用户 :user 现在是管理员',
            'user_is_no_longer_an_admin'        => '用户 :user 不再是管理员',
            'user_is_now_a_user'                => '用户 :user 现在是用户',
            'user_is_now_a_role'                => '用户 :user 现在是 :role',
            'account_deleted_successfully'      => '账户删除成功。',
            'account_deletion_error'            => '发生错误。请再试一次。',
            'user_is_not_a_patron'              => '该用户不是赞助者。',
            'all_benefits_granted_successfully' => '所有福利均已成功授予。',
            'error_granting_all_benefits'       => '尝试授予所有福利时发生错误。',
        ],
    ],

    'admin' => [
        'dungeonroute' => [
            'flash' => [
                'updated' => '地下城路线已成功更新。',
                'deleted' => '地下城路线已成功删除。',
                'claimed' => '地下城路线已成功认领。您现在是所有者。',
            ],
        ],
    ],

];
