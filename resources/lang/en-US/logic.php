<?php

return [
    'mdt' => [
        'io' => [
            'export_string' => [
                'category'                                          => [
                    'pull'     => 'Pull %d',
                    'title'    => 'Title',
                    'map_icon' => 'Map icon',
                ],
                'unable_to_find_mdt_enemy_for_kg_enemy'             => 'Unable to find MDT equivalent for Keystone.guru enemy with NPC %s (enemy_id: %d, npc_id: %d).',
                'unable_to_find_mdt_enemy_for_kg_enemy_details'     => 'This indicates that your route kills an enemy of which its NPC is known to MDT, but Keystone.guru hasn\'t coupled that enemy to an MDT equivalent yet (or it does not exist in MDT).',
                'unable_to_find_mdt_enemy_for_kg_caused_empty_pull' => 'This pull has been removed since all selected enemies could not be found in MDT, resulting in an otherwise empty pull.',
                'route_title_contains_non_ascii_char_bug'           => 'Your route title contains non-ascii characters that are known to trigger a yet unresolved encoding bug in Keystone.guru.
                                Your route title has been stripped of all offending characters, we apologise for the inconvenience and hope to resolve this issue soon.',
                'route_title_contains_non_ascii_char_bug_details'   => 'Old title: %s, new title: %s',
                'map_icon_contains_non_ascii_char_bug'              => 'One of your comments on a map icon has non-ascii characters that are known to trigger a yet unresolved encoding bug in Keystone.guru. Your map comment has been stripped of all offending characters, we apologise for the inconvenience and hope to resolve this issue soon.',
                'map_icon_contains_non_ascii_char_bug_details'      => 'Old comment: "%s", new comment: "%s"',
            ],
            'import_string' => [
                'category'                                             => [
                    'pull'   => 'Pull %d',
                    'object' => 'Object %d',
                ],
                'unable_to_find_floor_for_object'                      => 'Unable to find Keystone.guru floor that matches MDT floor ID %d.',
                'unable_to_find_floor_for_object_details'              => 'This indicates that MDT has a floor that Keystone.guru does not have.',
                'unable_to_find_mdt_enemy_for_clone_index'             => 'Unable to find MDT enemy for clone index %s and npc index %s.',
                'unable_to_find_mdt_enemy_for_clone_index_details'     => 'This indicates MDT has mapped an enemy that is not known in Keystone.guru yet.',
                'unable_to_find_kg_equivalent_for_mdt_enemy'           => 'Unable to find Keystone.guru equivalent for MDT enemy %s with NPC %s (id: %s).',
                'unable_to_find_kg_equivalent_for_mdt_enemy_details'   => 'This indicates that your route kills an enemy of which its NPC is known to Keystone.guru, but Keystone.guru doesn\'t have that enemy mapped yet.',
                'unable_to_find_awakened_enemy_for_final_boss'         => 'Unable to find Awakened Enemy %s (%s) at the final boss in %s.',
                'unable_to_find_awakened_enemy_for_final_boss_details' => 'This indicates Keystone.guru has a mapping error that will need to be corrected. Send the above warning to me and I\'ll correct it.',
                'unable_to_find_enemies_pull_skipped'                  => 'Failure to find enemies resulted in a pull being skipped.',
                'unable_to_find_enemies_pull_skipped_details'          => 'This may indicate MDT recently had an update that is not integrated in Keystone.guru yet.',
            ],
        ],
    ],
];
