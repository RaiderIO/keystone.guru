<?php

return [
    'error' => [
        'unauthenticated'                   => '未通过身份验证。请使用 OAuth 访问令牌连接。',
        'feature_inactive'                  => 'keystone.guru MCP 服务器对您的账户不可用。',
        'missing_scope'                     => '您的访问令牌缺少此工具所需的 :scope 权限范围。请重新连接并授予该权限。',
        'rate_limited'                      => '此工具的请求过多。请在 :seconds 秒后重试。',
        'request_refused'                   => '请求被拒绝。',
        'dungeon_not_found'                 => '没有与 ":dungeon" 匹配的活跃地下城。请使用 list_dungeons 获取有效的键。',
        'route_not_found'                   => '您没有键为 ":route" 的路线。请使用 list_my_routes 获取您的路线。',
        'pull_not_found'                    => '该路线没有第 :pull 次拉怪。',
        'unable_to_save_route'              => '无法保存路线。',
        'unable_to_save_pull'               => '无法保存拉怪。',
        'unable_to_delete_pull'             => '无法移除拉怪。',
        'no_enemies_given'                  => '请至少提供一个包 ID 或敌人 ID。',
        'unknown_pack_ids'                  => '这些包 ID 不属于该路线的地下城映射：:ids。',
        'unknown_enemy_ids'                 => '这些敌人 ID 不属于该路线的地下城映射：:ids。',
        'enemies_already_pulled'            => '这些敌人已在另一次拉怪中（敌人 ID => 拉怪编号）：:enemies。请先将它们从该次拉怪中移除，或不要包含它们。',
        'unknown_spell_ids'                 => '这些法术 ID 无法在拉怪中施放：:ids。请使用 get_route_options 获取拉怪可包含的法术。',
        'unknown_floor'                     => '楼层 :floor 不是该路线所属地下城的楼层。请使用 list_dungeons 获取楼层。',
        'unknown_dungeon_difficulty'        => '难度 :difficulty 不是此地下城的速通难度。请使用 list_dungeons 获取每个地下城的难度。',
        'unknown_dungeon_floor'             => '楼层 :floor 不是该地下城的楼层。请使用 list_dungeons 获取楼层。',
        'unknown_dungeon_start'             => '地下城起点 :start 不属于该路线的地下城映射。请使用 get_route_options 并指定地下城以获取其起点。',
        'specialization_not_of_class'       => '队伍成员 :member 的专精属于其他职业。',
        'specialization_not_of_spell_class' => '专精 :specialization 不是 :class 职业的专精。请使用 get_route_options 获取每个职业的专精。',
        'reorder_mismatch'                  => '顺序必须将该路线的每个拉怪 ID 恰好列出一次。路线拉怪 ID：:ids。',
    ],
];
