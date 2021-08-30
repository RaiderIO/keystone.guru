<?php


return [
    'apidungeonroute'      => [
        'mdt_generate_error'  => 'An error occurred generating your MDT string: %s',
        'mdt_generate_no_lua' => 'MDT importer is not configured properly. Please contact the admin about this issue',
    ],
    'dungeon'              => [
        'flash' => [
            'dungeon_created' => 'Dungeon created',
            'dungeon_updated' => 'Dungeon updated',
        ],
    ],
    'dungeonroute'         => [
        'unable_to_save' => 'Unable to save route',
        'flash'          => [
            'route_cloned_successfully' => 'Route cloned successfully',
            'route_updated'             => 'Route updated',
            'route_created'             => 'Route created'
        ],
    ],
    'dungeonroutediscover' => [
        'popular'           => 'Popular routes',
        'this_week_affixes' => 'This week\'s affixes',
        'next_week_affixes' => 'Next week\'s affixes',
        'new'               => 'New',
        'dungeon'           => [
            'popular'           => '%s popular routes',
            'this_week_affixes' => '%s this week',
            'next_week_affixes' => '%s next week',
            'new'               => '%s new routes',
        ],
    ],
    'expansion'            => [
        'flash' => [
            'unable_to_save_expansion' => 'Unable to save expansion',
            'expansion_updated'        => 'Expansion updated',
            'expansion_created'        => 'Expansion created',
        ],
    ],
    'oauthlogin'           => [
        'flash' => [
            'registered_successfully' => 'Registered successfully. Enjoy the website!',
            'user_exists'             => 'There is already a user with username %s. Did you already register before?',
            'email_exists'            => 'There is already a user with e-mail address %s. Did you already register before?',
        ],
    ],
    'register'             => [
        'flash'                 => [
            'registered_successfully' => 'Registered successfully. Enjoy the website!',
        ],
        'legal_agreed_required' => 'You have to agree to our legal terms to register.',
        'legal_agreed_accepted' => 'You have to agree to our legal terms to register.',
    ],
    'profile'              => [
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
    'team'                 => [
        'flash' => [
            'team_updated'                        => 'Team updated',
            'team_created'                        => 'Team created',
            'unable_to_find_team_for_invite_code' => 'Unable to find a team associated with this invite code',
            'invite_accept_success'               => 'Success! You are now a member of team %s.',
            'tag_created_successfully'            => 'Tag created successfully',
            'tag_already_exists'                  => 'This tag already exists',
        ],
    ],
    'user'                 => [
        'flash' => [
            'user_is_now_an_admin'         => 'User %s is now an admin',
            'user_is_no_longer_an_admin'   => 'User %s is no longer an admin',
            'user_is_now_a_user'           => 'User %s is now a user',
            'account_deleted_successfully' => 'Account deleted successfully.',
            'account_deletion_error'       => 'An error occurred. Please try again.',
            'user_is_not_a_patron'         => 'This user is not a Patron',
        ]
    ],
];