<?php

return [

    'mdt' => [
        'io' => [
            'export_string' => [
                'category' => [
                    'pull'     => '拉取 %d',
                    'title'    => '标题',
                    'map_icon' => '地图图标',
                ],
                'unable_to_find_mdt_enemy_for_kg_enemy'             => '无法找到 Keystone.guru 敌人对应的 MDT 敌人，NPC %s（enemy_id: %d, npc_id: %d）。',
                'unable_to_find_mdt_enemy_for_kg_enemy_details'     => '这表明您的路线中击杀了一个 MDT 已知的敌人 NPC，但 Keystone.guru 尚未将该敌人与 MDT 对应起来（或在 MDT 中不存在）。',
                'unable_to_find_mdt_enemy_for_kg_caused_empty_pull' => '此拉取已被移除，因为在 MDT 中找不到所有选择的敌人，导致拉取为空。',
                'route_title_contains_non_ascii_char_bug'           => '您的路线标题包含非 ASCII 字符，这些字符已知会触发 Keystone.guru 中一个尚未解决的编码错误。
                                                        您的路线标题中的所有违规字符已被删除，我们对造成的不便表示歉意，并希望尽快解决此问题。',
                'route_title_contains_non_ascii_char_bug_details' => '旧标题：%s，新标题：%s',
                'map_icon_contains_non_ascii_char_bug'            => '您的一个地图图标评论包含非 ASCII 字符，这些字符已知会触发 Keystone.guru 中一个尚未解决的编码错误。您的地图评论中的所有违规字符已被删除，我们对造成的不便表示歉意，并希望尽快解决此问题。',
                'map_icon_contains_non_ascii_char_bug_details'    => '旧评论："%s"，新评论："%s"',
            ],
            'import_string' => [
                'category' => [
                    'awakened_obelisks' => '觉醒方尖碑',
                    'pulls'             => '拉',
                    'notes'             => '笔记',
                    'arrows'            => '',
                    'pull'              => '拉 %d',
                    'object'            => '对象 %d',
                ],
                'object_out_of_bounds'                                 => '无法放置评论：无法放置评论 ":comment" 对象超出范围。',
                'limit_reached_pulls'                                  => '无法导入路线：超过最大 :limit 个拉。',
                'limit_reached_brushlines'                             => '无法导入路线：超过最大 :limit 行。',
                'limit_reached_paths'                                  => '无法导入路线：超过最大 :limit 条路径。',
                'limit_reached_arrows'                                 => '',
                'limit_reached_notes'                                  => '无法导入路线：超过最大 :limit 条注释。',
                'unable_to_find_floor_for_object'                      => '无法找到匹配 MDT 楼层 ID %d 的 Keystone.guru 楼层。',
                'unable_to_find_floor_for_object_details'              => '这表明 MDT 有一个楼层，而 Keystone.guru 没有。',
                'unable_to_find_mdt_enemy_for_clone_index'             => '无法找到克隆索引 %s 和 npc 索引 %s 的 MDT 敌人。',
                'unable_to_find_mdt_enemy_for_clone_index_details'     => '这表明 MDT 已经映射了一个 Keystone.guru 尚未知晓的敌人。',
                'unable_to_find_kg_equivalent_for_mdt_enemy'           => '无法找到 MDT 敌人 %s 和 NPC %s (id: %s) 的 Keystone.guru 等价物。',
                'unable_to_find_kg_equivalent_for_mdt_enemy_details'   => '这表明您的路线击杀了一个 Keystone.guru 已知的 NPC 的敌人，但 Keystone.guru 尚未映射该敌人。',
                'unable_to_find_awakened_enemy_for_final_boss'         => '无法在 %s 的最终首领处找到觉醒的敌人 %s (%s)。',
                'unable_to_find_awakened_enemy_for_final_boss_details' => '这表明 Keystone.guru 有一个映射错误需要修正。请将上述警告发送给我，我会纠正。',
                'unable_to_find_enemies_pull_skipped'                  => '未能找到敌人导致一个拉被跳过。',
                'unable_to_find_enemies_pull_skipped_details'          => '这可能表明 MDT 最近有一个更新尚未集成到 Keystone.guru 中。',
                'unable_to_find_awakened_obelisks'                     => '无法为您的地下城/周组合找到觉醒的方尖碑。您的觉醒方尖碑跳过将不会被导入。',
                'unable_to_find_awakened_obelisk_different_floor'      => '无法导入觉醒的方尖碑 :name，它位于与方尖碑本身不同的楼层。Keystone.guru 目前不支持这一点。',
                'unable_to_decode_mdt_import_string'                   => '无法解码 MDT 导入字符串',
                'unable_to_validate_mdt_import_string'                 => '无法验证 MDT 导入字符串',
            ],
        ],
    ],
    'npcservice' => [
        'all_dungeons' => '所有地下城',
    ],
    'combatlogservice' => [
        'analyze_combat_log' => [
            'verify_error'     => '无法验证战斗日志：错误。',
            'processing_error' => '无法处理战斗日志：错误。',
        ],
    ],

];
