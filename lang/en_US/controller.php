<?php

return [

    'admintools'                  => [
        'error' => [
            'mdt_string_parsing_failed'           => 'MDT string parsing failed. Did you really paste an MDT string?',
            'mdt_string_format_not_recognized'    => 'The MDT string format was not recognized.',
            'cli_weakauras_parser_not_found'      => 'cli_weakauras_parser not installed.',
            'invalid_mdt_string'                  => 'Invalid MDT string',
            'invalid_mdt_string_exception'        => 'Invalid MDT string: %s',
            'mdt_importer_not_configured'         => 'MDT importer is not configured properly. Please contact the admin about this issue.',
            'mdt_unable_to_find_npc_for_id'       => 'Unable to find NPC for id %d',
            'mdt_mismatched_health'               => 'NPC %s has mismatched health values, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces'         => 'NPC %s has mismatched enemy forces, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces_teeming' => 'NPC %s has mismatched enemy forces teeming, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_count'          => 'NPC %s has mismatched enemy count, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_type'           => 'NPC %s has mismatched enemy type, MDT: %s, KG: %s',
            'mdt_invalid_category'                => 'Invalid category',
        ],
        'flash' => [
            'message_banner_set_successfully' => 'Message banner set successfully',
            'thumbnail_regenerate_result'     => 'Dispatched :success jobs for :total routes. :failed failed.',
            'caches_dropped_successfully'     => 'Caches dropped successfully',
            'releases_exported'               => 'Releases exported',
            'exception'                       => [
                'internal_server_error' => 'Exception thrown in admin panel',
            ],
            'feature_toggle_activated'        => 'Feature :feature is now activated',
            'feature_toggle_deactivated'      => 'Feature :feature is now deactivated',
            'feature_forgotten'               => 'Feature :feature successfully forgotten',
            'read_only_mode_disabled'         => 'Read-only mode disabled',
            'read_only_mode_enabled'          => 'Read-only mode enabled',
        ],
    ],
    'apidungeonroute'             => [
        'mdt_generate_error'  => 'An error occurred generating your MDT string: %s',
        'mdt_generate_no_lua' => 'MDT importer is not configured properly. Please contact the admin about this issue',
    ],
    'apiuserreport'               => [
        'error' => [
            'unable_to_update_user_report' => 'Unable to update user report',
            'unable_to_save_report'        => 'Unable to save report',
        ],
    ],
    'brushline'                   => [
        'error' => [
            'unable_to_save_brushline'   => 'Unable to save line',
            'unable_to_delete_brushline' => 'Unable to delete line',
        ],
    ],
    'dungeon'                     => [
        'flash' => [
            'dungeon_created' => 'Dungeon created',
            'dungeon_updated' => 'Dungeon updated',
        ],
    ],
    'dungeonroute'                => [
        'unable_to_save' => 'Unable to save route',
        'flash'          => [
            'route_cloned_successfully' => 'Route cloned successfully',
            'route_updated'             => 'Route updated',
            'route_created'             => 'Route created',
        ],
    ],
    'dungeonroutediscover'        => [
        'popular'           => 'Popular routes',
        'this_week_affixes' => 'This week\'s affixes',
        'next_week_affixes' => 'Next week\'s affixes',
        'new'               => 'New',
        'season'            => [
            'popular'           => '%s popular routes',
            'this_week_affixes' => '%s this week',
            'next_week_affixes' => '%s next week',
            'new'               => '%s new routes',
        ],
        'dungeon'           => [
            'popular'           => '%s popular routes',
            'this_week_affixes' => '%s this week',
            'next_week_affixes' => '%s next week',
            'new'               => '%s new routes',
        ],
    ],
    'dungeonspeedrunrequirednpcs' => [
        'no_linked_npc' => 'No linked NPC',
        'flash'         => [
            'npc_added_successfully'   => 'Successfully added NPC',
            'npc_deleted_successfully' => 'Successfully removed NPC',
        ],
    ],
    'expansion'                   => [
        'flash' => [
            'unable_to_save_expansion' => 'Unable to save expansion',
            'expansion_updated'        => 'Expansion updated',
            'expansion_created'        => 'Expansion created',
        ],
    ],
    'generic'                     => [
        'error' => [
            'floor_not_found_in_dungeon' => 'Floor not part of dungeon',
            'not_found'                  => 'Not found',
        ],
    ],
    'oauthlogin'                  => [
        'flash' => [
            'registered_successfully' => 'Registered successfully. Enjoy the website!',
            'user_exists'             => 'There is already a user with username %s. Did you already register before?',
            'email_exists'            => 'There is already a user with e-mail address %s. Did you already register before?',
            'permission_denied'       => 'Unable to register - the request was denied. Please try again.',
            'read_only_mode_enabled'  => 'Read-only mode is enabled. You cannot register at this time.',
        ],
    ],
    'register'                    => [
        'flash'                 => [
            'registered_successfully' => 'Registered successfully. Enjoy the website!',
        ],
        'legal_agreed_required' => 'You have to agree to our legal terms to register.',
        'legal_agreed_accepted' => 'You have to agree to our legal terms to register.',
    ],
    'release'                     => [
        'error' => [
            'unable_to_save_release' => 'Unable to save release',
        ],
        'flash' => [
            'release_updated'  => 'Release updated',
            'release_created'  => 'Release created',
            'github_exception' => 'An error occurred communicating with Github: :message',
        ],
    ],
    'mappingversion'              => [
        'created_successfully'      => 'Added new mapping version!',
        'created_bare_successfully' => 'Added new bare mapping version!',
        'deleted_successfully'      => 'Deleted mapping version successfully',
    ],
    'mdtimport'                   => [
        'unknown_dungeon' => 'Unknown dungeon',
        'error'           => [
            'mdt_string_parsing_failed'             => 'MDT string parsing failed. Did you really paste an MDT string?',
            'mdt_string_format_not_recognized'      => 'The MDT string format was not recognized.',
            'cli_weakauras_parser_not_found'        => 'cli_weakauras_parser not installed.',
            'invalid_mdt_string_exception'          => 'Invalid MDT string: %s',
            'invalid_mdt_string'                    => 'Invalid MDT string',
            'mdt_importer_not_configured_properly'  => 'MDT importer is not configured properly. Please contact the admin about this issue.',
            'cannot_create_route_must_be_logged_in' => 'You must be logged in to create a route',
        ],
    ],
    'path'                        => [
        'error' => [
            'unable_to_save_path'   => 'Unable to save path',
            'unable_to_delete_path' => 'Unable to delete path',
        ],
    ],
    'patreon'                     => [
        'flash' => [
            'unlink_successful'       => 'Your Patreon account has successfully been unlinked.',
            'link_successful'         => 'Your Patreon has been linked successfully. Thank you!',
            'patreon_session_expired' => 'Your Patreon session has expired. Please try again.',
            'session_expired'         => 'Your session has expired. Please try again.',
            'patreon_error_occurred'  => 'An error occurred on Patreon\'s side. Please try again later.',
            'internal_error_occurred' => 'An error occurred while processing Patreon\'s response - it appears to be malformed. The error was logged and will be dealt with. Please try again later.',
        ],
    ],
    'profile'                     => [
        'flash' => [
            'email_already_in_use'             => 'That username is already in use.',
            'username_already_in_use'          => 'That username is already in use.',
            'profile_updated'                  => 'Profile updated',
            'unexpected_error_when_saving'     => 'An unexpected error occurred trying to save your profile',
            'privacy_settings_updated'         => 'Privacy settings updated',
            'password_changed'                 => 'Password changed',
            'new_password_equals_old_password' => 'New password equals the old password',
            'new_passwords_do_not_match'       => 'New passwords do not match',
            'current_password_is_incorrect'    => 'Current password is incorrect',
            'tag_created_successfully'         => 'Tag created successfully',
            'tag_already_exists'               => 'This tag already exists',
            'admins_cannot_delete_themselves'  => 'Admins cannot delete themselves!',
            'account_deleted_successfully'     => 'Account deleted successfully.',
            'error_deleting_account'           => 'An error occurred. Please try again.',
        ],
    ],
    'spell'                       => [
        'error' => [
            'unable_to_save_spell' => 'Unable to save spell',
        ],
        'flash' => [
            'spell_updated' => 'Spell updated',
            'spell_created' => 'Spell created',
        ],
    ],
    'team'                        => [
        'flash' => [
            'team_updated'                        => 'Team updated',
            'team_created'                        => 'Team created',
            'unable_to_find_team_for_invite_code' => 'Unable to find a team associated with this invite code',
            'invite_accept_success'               => 'Success! You are now a member of team %s.',
            'tag_created_successfully'            => 'Tag created successfully',
            'tag_already_exists'                  => 'This tag already exists',
        ],
    ],
    'user'                        => [
        'flash' => [
            'user_is_now_an_admin'              => 'User :user is now an admin',
            'user_is_no_longer_an_admin'        => 'User :user is no longer an admin',
            'user_is_now_a_user'                => 'User :user is now a user',
            'user_is_now_a_role'                => 'User :user is now a :role',
            'account_deleted_successfully'      => 'Account deleted successfully.',
            'account_deletion_error'            => 'An error occurred. Please try again.',
            'user_is_not_a_patron'              => 'This user is not a Patron.',
            'all_benefits_granted_successfully' => 'All benefits granted successfully.',
            'error_granting_all_benefits'       => 'An error occurred while trying to grant all benefits.',
        ],
    ],

];
