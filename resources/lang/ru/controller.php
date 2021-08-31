<?php


return [
    'admintools'           => [
        'error' => [
            'mdt_string_format_not_recognized' => '@todo ru: .admintools.error.mdt_string_format_not_recognized',
            'invalid_mdt_string'               => '@todo ru: .admintools.error.invalid_mdt_string',
            'invalid_mdt_string_exception'     => '@todo ru: .admintools.error.invalid_mdt_string_exception',
            'mdt_importer_not_configured'      => '@todo ru: .admintools.error.mdt_importer_not_configured',
        ],
        'flash' => [

        ],
    ],
    'apidungeonroute'      => [
        'mdt_generate_error'  => '@todo ru: .apidungeonroute.mdt_generate_error',
        'mdt_generate_no_lua' => '@todo ru: .apidungeonroute.mdt_generate_no_lua',
    ],
    'dungeon'              => [
        'flash' => [
            'dungeon_created' => '@todo ru: .dungeon.flash.dungeon_created',
            'dungeon_updated' => '@todo ru: .dungeon.flash.dungeon_updated',
        ],
    ],
    'dungeonroute'         => [
        'unable_to_save' => '@todo ru: .dungeonroute.unable_to_save',
        'flash'          => [
            'route_cloned_successfully' => '@todo ru: .dungeonroute.flash.route_cloned_successfully',
            'route_updated'             => '@todo ru: .dungeonroute.flash.route_updated',
            'route_created'             => '@todo ru: .dungeonroute.flash.route_created'
        ],
    ],
    'dungeonroutediscover' => [
        'popular'           => '@todo ru: .dungeonroutediscover.popular',
        'this_week_affixes' => '@todo ru: .dungeonroutediscover.this_week_affixes',
        'next_week_affixes' => '@todo ru: .dungeonroutediscover.next_week_affixes',
        'new'               => '@todo ru: .dungeonroutediscover.new',
        'dungeon'           => [
            'popular'           => '@todo ru: .dungeonroutediscover.dungeon.popular',
            'this_week_affixes' => '@todo ru: .dungeonroutediscover.dungeon.this_week_affixes',
            'next_week_affixes' => '@todo ru: .dungeonroutediscover.dungeon.next_week_affixes',
            'new'               => '@todo ru: .dungeonroutediscover.dungeon.new',
        ],
    ],
    'expansion'            => [
        'flash' => [
            'unable_to_save_expansion' => '@todo ru: .expansion.flash.unable_to_save_expansion',
            'expansion_updated'        => '@todo ru: .expansion.flash.expansion_updated',
            'expansion_created'        => '@todo ru: .expansion.flash.expansion_created',
        ],
    ],
    'oauthlogin'           => [
        'flash' => [
            'registered_successfully' => '@todo ru: .oauthlogin.flash.registered_successfully',
            'user_exists'             => '@todo ru: .oauthlogin.flash.user_exists',
            'email_exists'            => '@todo ru: .oauthlogin.flash.email_exists',
        ],
    ],
    'register'             => [
        'flash'                 => [
            'registered_successfully' => '@todo ru: .register.flash.registered_successfully',
        ],
        'legal_agreed_required' => '@todo ru: .register.legal_agreed_required',
        'legal_agreed_accepted' => '@todo ru: .register.legal_agreed_accepted',
    ],
    'release'              => [
        'error' => [
            'unable_to_save_release' => '@todo ru: .release.error.unable_to_save_release',
        ],
        'flash' => [
            'release_updated' => '@todo ru: .release.flash.release_updated',
            'release_created' => '@todo ru: .release.flash.release_created',
        ]
    ],
    'profile'              => [
        'flash' => [
            'email_already_in_use'             => '@todo ru: .profile.flash.email_already_in_use',
            'username_already_in_use'          => '@todo ru: .profile.flash.username_already_in_use',
            'profile_updated'                  => '@todo ru: .profile.flash.profile_updated',
            'unexpected_error_when_saving'     => '@todo ru: .profile.flash.unexpected_error_when_saving',
            'privacy_settings_updated'         => '@todo ru: .profile.flash.privacy_settings_updated',
            'password_changed'                 => '@todo ru: .profile.flash.password_changed',
            'new_password_equals_old_password' => '@todo ru: .profile.flash.new_password_equals_old_password',
            'new_passwords_do_not_match'       => '@todo ru: .profile.flash.new_passwords_do_not_match',
            'current_password_is_incorrect'    => '@todo ru: .profile.flash.current_password_is_incorrect',
            'tag_created_successfully'         => '@todo ru: .profile.flash.tag_created_successfully',
            'tag_already_exists'               => '@todo ru: .profile.flash.tag_already_exists',
            'admins_cannot_delete_themselves'  => '@todo ru: .profile.flash.admins_cannot_delete_themselves',
            'account_deleted_successfully'     => '@todo ru: .profile.flash.account_deleted_successfully',
            'error_deleting_account'           => '@todo ru: .profile.flash.error_deleting_account',
        ],
    ],
    'team'                 => [
        'flash' => [
            'team_updated'                        => '@todo ru: .team.flash.team_updated',
            'team_created'                        => '@todo ru: .team.flash.team_created',
            'unable_to_find_team_for_invite_code' => '@todo ru: .team.flash.unable_to_find_team_for_invite_code',
            'invite_accept_success'               => '@todo ru: .team.flash.invite_accept_success',
            'tag_created_successfully'            => '@todo ru: .team.flash.tag_created_successfully',
            'tag_already_exists'                  => '@todo ru: .team.flash.tag_already_exists',
        ],
    ],
    'user'                 => [
        'flash' => [
            'user_is_now_an_admin'         => '@todo ru: .user.flash.user_is_now_an_admin',
            'user_is_no_longer_an_admin'   => '@todo ru: .user.flash.user_is_no_longer_an_admin',
            'user_is_now_a_user'           => '@todo ru: .user.flash.user_is_now_a_user',
            'account_deleted_successfully' => '@todo ru: .user.flash.account_deleted_successfully',
            'account_deletion_error'       => '@todo ru: .user.flash.account_deletion_error',
            'user_is_not_a_patron'         => '@todo ru: .user.flash.user_is_not_a_patron',
        ]
    ],
];