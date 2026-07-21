<?php

return [

    'mdt' => [
        'io' => [
            'export_string' => [
                'category' => [
                    'pull'     => '拉 %d',
                    'title'    => '標題',
                    'map_icon' => '地圖圖示',
                ],
                'unable_to_find_mdt_enemy_for_kg_enemy'             => '無法找到 Keystone.guru 中的敵人 NPC %s 的 MDT 等效項（enemy_id: %d, npc_id: %d）。',
                'unable_to_find_mdt_enemy_for_kg_enemy_details'     => '這表示您的路線擊殺了一個已知其 NPC 的敵人，但 Keystone.guru 尚未將該敵人與 MDT 等效敵人配對（或者在 MDT 中不存在）。',
                'unable_to_find_mdt_enemy_for_kg_caused_empty_pull' => '由於無法在 MDT 中找到所有選定的敵人，這次拉怪已被移除，導致拉怪沒有內容。',
                'route_title_contains_non_ascii_char_bug'           => '您的路徑標題中含有非 ASCII 字元，這些字元已知會觸發 Keystone.guru 中尚未解決的編碼錯誤。
                        您的路徑標題已移除所有引發問題的字元，對此造成的不便我們深感抱歉，並希望儘快解決此問題。',
                'route_title_contains_non_ascii_char_bug_details' => '舊標題：%s，新標題：%s',
                'map_icon_contains_non_ascii_char_bug'            => '您在地圖圖示上的評論中含有非 ASCII 字元，這些字元已知會觸發 Keystone.guru 中尚未解決的編碼錯誤。我們已移除所有引發問題的字元，對此造成的不便我們深感抱歉，並希望儘快解決此問題。',
                'map_icon_contains_non_ascii_char_bug_details'    => '舊評論："%s"，新評論："%s"',
            ],
            'import_string' => [
                'category' => [
                    'awakened_obelisks' => '覺醒方尖碑',
                    'pulls'             => '拉怪',
                    'notes'             => '筆記',
                    'arrows'            => '',
                    'pull'              => '拉怪 %d',
                    'object'            => '物件 %d',
                ],
                'object_out_of_bounds'                                 => '無法放置評論：無法放置評論 ":comment"，物件超出範圍。',
                'limit_reached_pulls'                                  => '無法匯入路線：超過 :limit 拉怪的最大限制。',
                'limit_reached_brushlines'                             => '無法匯入路線：超過 :limit 行的最大限制。',
                'limit_reached_paths'                                  => '無法匯入路線：超過 :limit 路徑的最大限制。',
                'limit_reached_arrows'                                 => '',
                'limit_reached_notes'                                  => '無法匯入路線：超過 :limit 筆記的最大限制。',
                'unable_to_find_floor_for_object'                      => '無法找到與 MDT 樓層 ID %d 匹配的 Keystone.guru 樓層。',
                'unable_to_find_floor_for_object_details'              => '這表示 MDT 有一個 Keystone.guru 沒有的樓層。',
                'unable_to_find_mdt_enemy_for_clone_index'             => '無法為克隆索引 %s 和 npc 索引 %s 找到 MDT 敵人。',
                'unable_to_find_mdt_enemy_for_clone_index_details'     => '這表示 MDT 映射了一個 Keystone.guru 尚未知的敵人。',
                'unable_to_find_kg_equivalent_for_mdt_enemy'           => '無法找到 MDT 敵人 %s 的 Keystone.guru 等效物，其 NPC %s (id: %s)。',
                'unable_to_find_kg_equivalent_for_mdt_enemy_details'   => '這表示您的路線擊殺了一個已知其 NPC 的敵人，但 Keystone.guru 尚未將該敵人映射。',
                'unable_to_find_awakened_enemy_for_final_boss'         => '無法在 %s 的最終首領中找到覺醒敵人 %s (%s)。',
                'unable_to_find_awakened_enemy_for_final_boss_details' => '這表示 Keystone.guru 有一個映射錯誤，需要修正。請將上述警告發給我，我會修正它。',
                'unable_to_find_enemies_pull_skipped'                  => '找不到敵人，導致拉怪被跳過。',
                'unable_to_find_enemies_pull_skipped_details'          => '這可能表示 MDT 最近有更新，而 Keystone.guru 尚未整合。',
                'unable_to_find_awakened_obelisks'                     => '無法為您的地城/週組合找到覺醒方尖碑。您的覺醒方尖碑跳過將不會被匯入。',
                'unable_to_find_awakened_obelisk_different_floor'      => '無法匯入覺醒方尖碑 :name，它位於與方尖碑本身不同的樓層。Keystone.guru 目前不支援此功能。',
                'unable_to_decode_mdt_import_string'                   => '無法解碼 MDT 匯入字串',
                'unable_to_validate_mdt_import_string'                 => '無法驗證 MDT 匯入字串',
            ],
        ],
    ],
    'npcservice' => [
        'all_dungeons' => '所有地城',
    ],
    'combatlogservice' => [
        'analyze_combat_log' => [
            'verify_error'     => '無法驗證戰鬥日誌：錯誤。',
            'processing_error' => '無法處理戰鬥日誌：錯誤。',
        ],
    ],

];
