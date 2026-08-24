<?php

return [

    'home' => [
        'front_page' => 'Keystone.guru',
        'compendium' => [
            'npc'          => 'NPC图鉴',
            'npc_show'     => ':name',
            'spell'        => '法术图鉴',
            'spell_show'   => ':name',
            'activity'     => '图鉴动态',
            'activity_day' => ':date',
            'class'        => '按职业',
        ],
        'affixes' => '词缀',
        'about'   => '关于',
        'credits' => '致谢',
        'legal'   => [
            'cookies' => 'Cookie',
            'privacy' => '隐私',
            'terms'   => '条款',
        ],
        'routes'              => '路线',
        'routes_expansion'    => ':expansion 路线',
        'routes_game_version' => ':gameVersion 路线',
        'gameversion'         => [
            'update'  => ':gameVersion',
            'dungeon' => [
                'heatmap' => '热图',
                'explore' => '探索',
            ],
        ],
        'dungeonroute' => [
            'new' => '新路线',
        ],
        'dungeonroutes' => [
            'search'        => '搜索',
            'popular'       => '热门',
            'new'           => '新建',
            'routes_season' => '赛季:season',
            'season'        => [
                'popular' => '热门',
                'new'     => '新建',
            ],
            'discoverdungeon' => [
                'popular' => '热门',
                'new'     => '新建',
            ],
        ],
        'my_favorites'     => '我的收藏',
        'account_settings' => '账户设置',
        'my_routes'        => '我的路线',
        'my_tags'          => '我的标签',
        'my_teams'         => '我的团队',
        'overview'         => '概览',
        'new_team'         => '新团队',
        'edit_team'        => '编辑团队',
        'join_team'        => '加入团队',
        'admin'            => [
            'admin'   => '管理',
            'affixes' => [
                'affixes'    => '词缀',
                'new_affix'  => '新建词缀',
                'edit_affix' => '编辑 :affix',
            ],
            'seasons' => [
                'seasons'     => '赛季',
                'new_season'  => '新建赛季',
                'edit_season' => '编辑 :season',
            ],
            'affixgroups' => [
                'new_affixgroup'  => '新建词缀组',
                'edit_affixgroup' => '编辑词缀组',
            ],
            'tools' => [
                'admin_tools'                                 => '管理工具',
                'view_exported_dungeondata'                   => '查看导出的地下城数据',
                'select_exception'                            => '选择例外',
                'mdt_diff'                                    => 'MDT差异',
                'view_mdt_string_contents'                    => '查看MDT字符串内容',
                'import_npcs'                                 => '导入NPC',
                'spells_missing_info'                         => '法术缺少信息',
                'npcs_missing_display_id'                     => 'NPC缺少显示ID',
                'thumbnails_regenerate'                       => '重新生成缩略图',
                'combat_log_regenerate'                       => '重新生成 ARC 路线',
                'combat_log_criteria'                         => 'NPC图鉴解析条件',
                'combat_log_run_data'                         => '清理战斗日志运行数据',
                'combat_log_route_coverage'                   => 'ARC 敌方部队覆盖率',
                'dungeonroute_view'                           => '查看地下城路线',
                'dungeonroute_view_contents'                  => '路线内容',
                'dungeonroute_mapping_version_usage'          => '映射版本使用情况',
                'enemyforces_import'                          => '导入敌方部队',
                'enemyforces_recalculate'                     => '重新计算敌方部队',
                'features_list'                               => '功能',
                'mdt_dungeon_mapping_hash'                    => '地下城映射哈希',
                'mdt_dungeon_mapping_version_accuracy'        => '地下城映射版本准确度',
                'mdt_dungeon_mapping_version_to_mdt_mapping'  => '地下城映射版本与MDT映射对照',
                'mdt_dungeonroute'                            => 'MDT地下城路线',
                'mdt_list'                                    => 'MDT字符串列表',
                'messagebanner_set'                           => '设置消息横幅',
                'npc_manage_spell_visibility'                 => '管理NPC法术可见性',
                'wagogg_import_ingame_coordinates'            => '导入游戏内坐标',
                'artisancommands_backfill_kill_zone_enemy_id' => '回填拉怪敌人ID',
            ],
            'expansions' => [
                'expansions'     => '资料片',
                'new_expansion'  => '新建资料片',
                'edit_expansion' => '编辑资料片',
            ],
            'dungeons' => [
                'dungeons'     => '地下城',
                'new_dungeon'  => '新建地下城',
                'edit_dungeon' => '编辑:dungeon',
            ],
            'floors' => [
                'new_floor'  => '新建楼层',
                'edit_floor' => '编辑楼层',
            ],
            'dungeonspeedrunrequirednpc' => [
                'new_dungeonspeedrunrequirednpc' => '新建 :difficulty 难度地下城速通所需NPC',
            ],
            'npcs' => [
                'npcs'     => 'NPC',
                'new_npc'  => '新建NPC',
                'edit_npc' => '编辑:npc',
            ],
            'npcenemyforces' => [
                'new_npc_enemy_forces'  => '新建NPC敌方力量',
                'edit_npc_enemy_forces' => '编辑NPC敌方力量',
            ],
            'npchealth' => [
                'new_npc_health'  => '新建NPC生命值',
                'edit_npc_health' => '编辑NPC生命值',
            ],
            'spells' => [
                'spells'     => '法术',
                'new_spell'  => '新建法术',
                'edit_spell' => '编辑法术',
            ],
            'users' => [
                'users' => '用户',
            ],
            'user_reports' => [
                'user_reports' => '用户报告',
            ],
        ],
    ],

];
