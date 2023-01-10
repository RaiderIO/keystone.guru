<?php


return [
    'admintools'                  => [
        'error' => [
            'mdt_string_format_not_recognized'    => '@todo de: .admintools.error.mdt_string_format_not_recognized',
            'invalid_mdt_string'                  => '@todo de: .admintools.error.invalid_mdt_string',
            'invalid_mdt_string_exception'        => '@todo de: .admintools.error.invalid_mdt_string_exception',
            'mdt_importer_not_configured'         => '@todo de: .admintools.error.mdt_importer_not_configured',
            'mdt_unable_to_find_npc_for_id'       => '@todo de: .admintools.error.mdt_unable_to_find_npc_for_id',
            'mdt_mismatched_health'               => '@todo de: .admintools.error.mdt_mismatched_health',
            'mdt_mismatched_enemy_forces'         => '@todo de: .admintools.error.mdt_mismatched_enemy_forces',
            'mdt_mismatched_enemy_forces_teeming' => '@todo de: .admintools.error.mdt_mismatched_enemy_forces_teeming',
            'mdt_mismatched_enemy_count'          => '@todo de: .admintools.error.mdt_mismatched_enemy_count',
            'mdt_mismatched_enemy_type'           => '@todo de: .admintools.error.mdt_mismatched_enemy_type',
            'mdt_invalid_category'                => '@todo de: .admintools.error.mdt_invalid_category',
        ],
        'flash' => [
            'caches_dropped_successfully' => '@todo de: .admintools.flash.caches_dropped_successfully',
            'releases_exported'           => '@todo de: .admintools.flash.releases_exported',
            'exception'                   => [
                'token_mismatch'        => '@todo de: .admintools.flash.exception.token_mismatch',
                'internal_server_error' => '@todo de: .admintools.flash.exception.internal_server_error',
            ],
        ],
    ],
    'apidungeonroute'             => [
        'mdt_generate_error'  => '@todo de: .apidungeonroute.mdt_generate_error',
        'mdt_generate_no_lua' => '@todo de: .apidungeonroute.mdt_generate_no_lua',
    ],
    'apiuserreport'               => [
        'error' => [
            'unable_to_update_user_report' => '@todo de: .apiuserreport.error.unable_to_update_user_report',
            'unable_to_save_report'        => '@todo de: .apiuserreport.error.unable_to_save_report',
        ],
    ],
    'dungeon'                     => [
        'flash' => [
            'dungeon_created' => '@todo de: .dungeon.flash.dungeon_created',
            'dungeon_updated' => '@todo de: .dungeon.flash.dungeon_updated',
        ],
    ],
    'dungeonroute'                => [
        'unable_to_save' => '@todo de: .dungeonroute.unable_to_save',
        'flash'          => [
            'route_cloned_successfully' => '@todo de: .dungeonroute.flash.route_cloned_successfully',
            'route_updated'             => '@todo de: .dungeonroute.flash.route_updated',
            'route_created'             => '@todo de: .dungeonroute.flash.route_created',
        ],
    ],
    'dungeonroutediscover'        => [
        'popular'           => '@todo de: .dungeonroutediscover.popular',
        'this_week_affixes' => '@todo de: .dungeonroutediscover.this_week_affixes',
        'next_week_affixes' => '@todo de: .dungeonroutediscover.next_week_affixes',
        'new'               => '@todo de: .dungeonroutediscover.new',
        'dungeon'           => [
            'popular'           => '@todo de: .dungeonroutediscover.dungeon.popular',
            'this_week_affixes' => '@todo de: .dungeonroutediscover.dungeon.this_week_affixes',
            'next_week_affixes' => '@todo de: .dungeonroutediscover.dungeon.next_week_affixes',
            'new'               => '@todo de: .dungeonroutediscover.dungeon.new',
        ],
    ],
    'dungeonspeedrunrequirednpcs' => [
        'no_linked_npc' => '@todo de: .dungeonspeedrunrequirednpcs.no_linked_npc',
        'flash'         => [
            'npc_added_successfully'   => '@todo de: .dungeonspeedrunrequirednpcs.flash.npc_added_successfully',
            'npc_deleted_successfully' => '@todo de: .dungeonspeedrunrequirednpcs.flash.npc_deleted_successfully',
        ],
    ],
    'expansion'                   => [
        'flash' => [
            'unable_to_save_expansion' => '@todo de: .expansion.flash.unable_to_save_expansion',
            'expansion_updated'        => '@todo de: .expansion.flash.expansion_updated',
            'expansion_created'        => '@todo de: .expansion.flash.expansion_created',
        ],
    ],
    'oauthlogin'                  => [
        'flash' => [
            'registered_successfully' => '@todo de: .oauthlogin.flash.registered_successfully',
            'user_exists'             => '@todo de: .oauthlogin.flash.user_exists',
            'email_exists'            => '@todo de: .oauthlogin.flash.email_exists',
        ],
    ],
    'register'                    => [
        'flash'                 => [
            'registered_successfully' => '@todo de: .register.flash.registered_successfully',
        ],
        'legal_agreed_required' => '@todo de: .register.legal_agreed_required',
        'legal_agreed_accepted' => '@todo de: .register.legal_agreed_accepted',
    ],
    'release'                     => [
        'error' => [
            'unable_to_save_release' => '@todo de: .release.error.unable_to_save_release',
        ],
        'flash' => [
            'release_updated'  => '@todo de: .release.flash.release_updated',
            'release_created'  => '@todo de: .release.flash.release_created',
            'github_exception' => '@todo de: .release.flash.github_exception',
        ],
    ],
    'mappingversion'              => [
        'created_successfully' => '@todo de: .mappingversion.created_successfully',
        'deleted_successfully' => '@todo de: .mappingversion.deleted_successfully',
    ],
    'mdtimport'                   => [
        'unknown_dungeon' => '@todo de: .mdtimport.unknown_dungeon',
        'error'           => [
            'mdt_string_format_not_recognized'      => '@todo de: .mdtimport.error.mdt_string_format_not_recognized',
            'invalid_mdt_string_exception'          => '@todo de: .mdtimport.error.invalid_mdt_string_exception',
            'invalid_mdt_string'                    => '@todo de: .mdtimport.error.invalid_mdt_string',
            'mdt_importer_not_configured_properly'  => '@todo de: .mdtimport.error.mdt_importer_not_configured_properly',
            'cannot_create_route_must_be_logged_in' => '@todo de: .mdtimport.error.cannot_create_route_must_be_logged_in',
        ],
    ],
    'patreon'                     => [
        'flash' => [
            'unlink_successful'       => '@todo de: .patreon.flash.unlink_successful',
            'link_successful'         => '@todo de: .patreon.flash.link_successful',
            'patreon_session_expired' => '@todo de: .patreon.flash.patreon_session_expired',
            'session_expired'         => '@todo de: .patreon.flash.session_expired',
            'patreon_error_occurred'  => '@todo de: .patreon.flash.patreon_error_occurred',
            'internal_error_occurred' => '@todo de: .patreon.flash.internal_error_occurred',
        ],
    ],
    'profile'                     => [
        'flash' => [
            'email_already_in_use'             => '@todo de: .profile.flash.email_already_in_use',
            'username_already_in_use'          => '@todo de: .profile.flash.username_already_in_use',
            'profile_updated'                  => '@todo de: .profile.flash.profile_updated',
            'unexpected_error_when_saving'     => '@todo de: .profile.flash.unexpected_error_when_saving',
            'privacy_settings_updated'         => '@todo de: .profile.flash.privacy_settings_updated',
            'password_changed'                 => '@todo de: .profile.flash.password_changed',
            'new_password_equals_old_password' => '@todo de: .profile.flash.new_password_equals_old_password',
            'new_passwords_do_not_match'       => '@todo de: .profile.flash.new_passwords_do_not_match',
            'current_password_is_incorrect'    => '@todo de: .profile.flash.current_password_is_incorrect',
            'tag_created_successfully'         => '@todo de: .profile.flash.tag_created_successfully',
            'tag_already_exists'               => '@todo de: .profile.flash.tag_already_exists',
            'admins_cannot_delete_themselves'  => '@todo de: .profile.flash.admins_cannot_delete_themselves',
            'account_deleted_successfully'     => '@todo de: .profile.flash.account_deleted_successfully',
            'error_deleting_account'           => '@todo de: .profile.flash.error_deleting_account',
        ],
    ],
    'spell'                       => [
        'error' => [
            'unable_to_save_spell' => '@todo de: .spell.error.unable_to_save_spell',
        ],
        'flash' => [
            'spell_updated' => '@todo de: .spell.flash.spell_updated',
            'spell_created' => '@todo de: .spell.flash.spell_created',
        ],
    ],
    'team'                        => [
        'flash' => [
            'team_updated'                        => '@todo de: .team.flash.team_updated',
            'team_created'                        => '@todo de: .team.flash.team_created',
            'unable_to_find_team_for_invite_code' => '@todo de: .team.flash.unable_to_find_team_for_invite_code',
            'invite_accept_success'               => '@todo de: .team.flash.invite_accept_success',
            'tag_created_successfully'            => '@todo de: .team.flash.tag_created_successfully',
            'tag_already_exists'                  => '@todo de: .team.flash.tag_already_exists',
        ],
    ],
    'user'                        => [
        'flash' => [
            'user_is_now_an_admin'         => '@todo de: .user.flash.user_is_now_an_admin',
            'user_is_no_longer_an_admin'   => '@todo de: .user.flash.user_is_no_longer_an_admin',
            'user_is_now_a_user'           => '@todo de: .user.flash.user_is_now_a_user',
            'account_deleted_successfully' => '@todo de: .user.flash.account_deleted_successfully',
            'account_deletion_error'       => '@todo de: .user.flash.account_deletion_error',
            'user_is_not_a_patron'         => '@todo de: .user.flash.user_is_not_a_patron',
        ],
    ],
];
