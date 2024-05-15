<?php

return [

    'admintools'                  => [
        'error' => [
            'mdt_string_parsing_failed'           => '',
            'mdt_string_format_not_recognized'    => '',
            'invalid_mdt_string'                  => '',
            'invalid_mdt_string_exception'        => '',
            'mdt_importer_not_configured'         => '',
            'mdt_unable_to_find_npc_for_id'       => '',
            'mdt_mismatched_health'               => '',
            'mdt_mismatched_enemy_forces'         => '',
            'mdt_mismatched_enemy_forces_teeming' => '',
            'mdt_mismatched_enemy_count'          => '',
            'mdt_mismatched_enemy_type'           => '',
            'mdt_invalid_category'                => '',
        ],
        'flash' => [
            'thumbnail_regenerate_result' => '',
            'caches_dropped_successfully' => '',
            'releases_exported'           => '',
            'exception'                   => [
                'internal_server_error' => '',
            ],
        ],
    ],
    'apidungeonroute'             => [
        'mdt_generate_error'  => '',
        'mdt_generate_no_lua' => '',
    ],
    'apiuserreport'               => [
        'error' => [
            'unable_to_update_user_report' => '',
            'unable_to_save_report'        => '',
        ],
    ],
    'brushline'                   => [
        'error' => [
            'unable_to_save_brushline'   => '',
            'unable_to_delete_brushline' => '',
        ],
    ],
    'dungeon'                     => [
        'flash' => [
            'dungeon_created' => '',
            'dungeon_updated' => '',
        ],
    ],
    'dungeonroute'                => [
        'unable_to_save' => '',
        'flash'          => [
            'route_cloned_successfully' => '',
            'route_updated'             => '',
            'route_created'             => '',
        ],
    ],
    'dungeonroutediscover'        => [
        'popular'           => '',
        'this_week_affixes' => '',
        'next_week_affixes' => '',
        'new'               => '',
        'season'            => [
            'popular'           => '',
            'this_week_affixes' => '',
            'next_week_affixes' => '',
            'new'               => '',
        ],
        'dungeon'           => [
            'popular'           => '',
            'this_week_affixes' => '',
            'next_week_affixes' => '',
            'new'               => '',
        ],
    ],
    'dungeonspeedrunrequirednpcs' => [
        'no_linked_npc' => '',
        'flash'         => [
            'npc_added_successfully'   => '',
            'npc_deleted_successfully' => '',
        ],
    ],
    'expansion'                   => [
        'flash' => [
            'unable_to_save_expansion' => '',
            'expansion_updated'        => '',
            'expansion_created'        => '',
        ],
    ],
    'generic'                     => [
        'error' => [
            'floor_not_found_in_dungeon' => '',
            'not_found'                  => '',
        ],
    ],
    'oauthlogin'                  => [
        'flash' => [
            'registered_successfully' => '',
            'user_exists'             => '',
            'email_exists'            => '',
            'permission_denied'       => '',
        ],
    ],
    'register'                    => [
        'flash'                 => [
            'registered_successfully' => '',
        ],
        'legal_agreed_required' => '',
        'legal_agreed_accepted' => '',
    ],
    'release'                     => [
        'error' => [
            'unable_to_save_release' => '',
        ],
        'flash' => [
            'release_updated'  => '',
            'release_created'  => '',
            'github_exception' => '',
        ],
    ],
    'mappingversion'              => [
        'created_successfully'      => '',
        'created_bare_successfully' => '',
        'deleted_successfully'      => '',
    ],
    'mdtimport'                   => [
        'unknown_dungeon' => '',
        'error'           => [
            'mdt_string_parsing_failed'             => '',
            'mdt_string_format_not_recognized'      => '',
            'invalid_mdt_string_exception'          => '',
            'invalid_mdt_string'                    => '',
            'mdt_importer_not_configured_properly'  => '',
            'cannot_create_route_must_be_logged_in' => '',
        ],
    ],
    'path'                        => [
        'error' => [
            'unable_to_save_path'   => '',
            'unable_to_delete_path' => '',
        ],
    ],
    'patreon'                     => [
        'flash' => [
            'unlink_successful'       => '',
            'link_successful'         => '',
            'patreon_session_expired' => '',
            'session_expired'         => '',
            'patreon_error_occurred'  => '',
            'internal_error_occurred' => '',
        ],
    ],
    'profile'                     => [
        'flash' => [
            'email_already_in_use'             => '',
            'username_already_in_use'          => '',
            'profile_updated'                  => '',
            'unexpected_error_when_saving'     => '',
            'privacy_settings_updated'         => '',
            'password_changed'                 => '',
            'new_password_equals_old_password' => '',
            'new_passwords_do_not_match'       => '',
            'current_password_is_incorrect'    => '',
            'tag_created_successfully'         => '',
            'tag_already_exists'               => '',
            'admins_cannot_delete_themselves'  => '',
            'account_deleted_successfully'     => '',
            'error_deleting_account'           => '',
        ],
    ],
    'spell'                       => [
        'error' => [
            'unable_to_save_spell' => '',
        ],
        'flash' => [
            'spell_updated' => '',
            'spell_created' => '',
        ],
    ],
    'team'                        => [
        'flash' => [
            'team_updated'                        => '',
            'team_created'                        => '',
            'unable_to_find_team_for_invite_code' => '',
            'invite_accept_success'               => '',
            'tag_created_successfully'            => '',
            'tag_already_exists'                  => '',
        ],
    ],
    'user'                        => [
        'flash' => [
            'user_is_now_an_admin'              => '',
            'user_is_no_longer_an_admin'        => '',
            'user_is_now_a_user'                => '',
            'account_deleted_successfully'      => '',
            'account_deletion_error'            => '',
            'user_is_not_a_patron'              => '',
            'all_benefits_granted_successfully' => '',
            'error_granting_all_benefits'       => '',
        ],
    ],

];
