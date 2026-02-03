<?php

return [

    'admintools'                  => [
        'error' => [
            'mdt_string_parsing_failed'           => 'MDT-String-Parsing fehlgeschlagen. Haben Sie wirklich einen MDT-String eingefügt?',
            'mdt_string_format_not_recognized'    => 'Das MDT-String-Format wurde nicht erkannt.',
            'cli_weakauras_parser_not_found'      => 'cli_weakauras_parser nicht installiert.',
            'invalid_mdt_string'                  => 'Ungültiger MDT-String',
            'invalid_mdt_string_exception'        => 'Ungültiger MDT-String: %s',
            'mdt_importer_not_configured'         => 'MDT-Importer ist nicht richtig konfiguriert. Bitte kontaktieren Sie den Administrator über dieses Problem.',
            'mdt_unable_to_find_npc_for_id'       => 'Konnte NPC für ID %d nicht finden',
            'mdt_mismatched_health'               => 'NPC %s hat nicht übereinstimmende Gesundheitswerte, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces'         => 'NPC %s hat nicht übereinstimmende Feindkräfte, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces_teeming' => 'NPC %s hat nicht übereinstimmende wimmelnde Feindkräfte, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_count'          => 'NPC %s hat eine nicht übereinstimmende Feindanzahl, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_type'           => 'NPC %s hat nicht übereinstimmenden Feindtyp, MDT: %s, KG: %s',
            'mdt_invalid_category'                => 'Ungültige Kategorie',
        ],
        'flash' => [
            'message_banner_set_successfully' => 'Nachrichtenbanner erfolgreich gesetzt',
            'thumbnail_regenerate_result'     => ':success Jobs für :total Routen versendet. :failed fehlgeschlagen.',
            'caches_dropped_successfully'     => 'Caches erfolgreich gelöscht',
            'releases_exported'               => 'Releases exportiert',
            'exception'                       => 'Ausnahme im Admin-Panel ausgelöst',
            'feature_toggle_activated'        => 'Feature :feature ist jetzt aktiviert',
            'feature_toggle_deactivated'      => 'Feature :feature ist jetzt deaktiviert',
            'feature_forgotten'               => 'Feature :feature erfolgreich vergessen',
            'read_only_mode_disabled'         => 'Schreibgeschützter Modus deaktiviert',
            'read_only_mode_enabled'          => 'Schreibgeschützter Modus aktiviert',
        ],
    ],
    'apidungeonroute'             => [
        'mdt_generate_error'  => 'Ein Fehler ist beim Generieren Ihres MDT-Strings aufgetreten: %s',
        'mdt_generate_no_lua' => 'MDT-Importer ist nicht richtig konfiguriert. Bitte kontaktieren Sie den Administrator über dieses Problem',
    ],
    'apiuserreport'               => [
        'error' => [
            'unable_to_update_user_report' => 'Benutzerbericht konnte nicht aktualisiert werden',
            'unable_to_save_report'        => 'Bericht konnte nicht gespeichert werden',
        ],
    ],
    'brushline'                   => [
        'error' => [
            'unable_to_save_brushline'   => 'Linie konnte nicht gespeichert werden',
            'unable_to_delete_brushline' => 'Linie konnte nicht gelöscht werden',
        ],
    ],
    'dungeon'                     => [
        'flash' => [
            'dungeon_created' => 'Dungeon erstellt',
            'dungeon_updated' => 'Dungeon aktualisiert',
        ],
    ],
    'dungeonroute'                => [
        'unable_to_save' => 'Route konnte nicht gespeichert werden',
        'flash'          => [
            'route_cloned_successfully' => 'Route erfolgreich geklont',
            'route_updated'             => 'Route aktualisiert',
            'route_created'             => 'Route erstellt',
        ],
    ],
    'dungeonroutediscover'        => [
        'popular'           => 'Beliebte Routen',
        'this_week_affixes' => 'Affixe dieser Woche',
        'next_week_affixes' => 'Affixe der nächsten Woche',
        'new'               => 'Neu',
        'season'            => [
            'popular'           => '%s beliebte Routen',
            'this_week_affixes' => '%s diese Woche',
            'next_week_affixes' => '%s nächste Woche',
            'new'               => '%s neue Routen',
        ],
        'dungeon'           => [
            'popular'           => '%s beliebte Routen',
            'this_week_affixes' => '%s diese Woche',
            'next_week_affixes' => '%s nächste Woche',
            'new'               => '%s neue Routen',
        ],
    ],
    'dungeonspeedrunrequirednpcs' => [
        'no_linked_npc' => 'Kein verknüpfter NPC',
        'flash'         => [
            'npc_added_successfully'   => 'NPC erfolgreich hinzugefügt',
            'npc_deleted_successfully' => 'NPC erfolgreich entfernt',
        ],
    ],
    'expansion'                   => [
        'flash' => [
            'unable_to_save_expansion' => 'Erweiterung konnte nicht gespeichert werden',
            'expansion_updated'        => 'Erweiterung aktualisiert',
            'expansion_created'        => 'Erweiterung erstellt',
        ],
    ],
    'generic'                     => [
        'error' => [
            'floor_not_found_in_dungeon' => 'Etage nicht Teil des Dungeons',
            'not_found'                  => 'Nicht gefunden',
        ],
    ],
    'oauthlogin'                  => [
        'flash' => [
            'registered_successfully' => 'Erfolgreich registriert. Viel Spaß auf der Website!',
            'user_exists'             => 'Es gibt bereits einen Benutzer mit dem Benutzernamen %s. Haben Sie sich schon vorher registriert?',
            'email_exists'            => 'Es gibt bereits einen Benutzer mit der E-Mail-Adresse %s. Haben Sie sich schon vorher registriert?',
            'permission_denied'       => 'Registrierung nicht möglich - die Anfrage wurde abgelehnt. Bitte versuchen Sie es erneut.',
            'read_only_mode_enabled'  => 'Schreibgeschützter Modus ist aktiviert. Sie können sich derzeit nicht registrieren.',
        ],
    ],
    'register'                    => [
        'flash'                 => [
            'registered_successfully' => 'Erfolgreich registriert. Viel Spaß auf der Website!',
        ],
        'legal_agreed_required' => 'Sie müssen unseren rechtlichen Bedingungen zustimmen, um sich zu registrieren.',
        'legal_agreed_accepted' => 'Sie müssen unseren rechtlichen Bedingungen zustimmen, um sich zu registrieren.',
    ],
    'release'                     => [
        'error' => [
            'unable_to_save_release' => 'Release konnte nicht gespeichert werden',
        ],
        'flash' => [
            'release_updated'  => 'Release aktualisiert',
            'release_created'  => 'Release erstellt',
            'github_exception' => 'Es ist ein Fehler bei der Kommunikation mit Github aufgetreten: :message',
        ],
    ],
    'mappingversion'              => [
        'created_successfully'      => 'Neue Mapping-Version hinzugefügt!',
        'created_bare_successfully' => 'Neue einfache Mapping-Version hinzugefügt!',
        'deleted_successfully'      => 'Mapping-Version erfolgreich gelöscht',
    ],
    'mdtimport'                   => [
        'unknown_dungeon' => 'Unbekannter Dungeon',
        'error'           => [
            'mdt_string_parsing_failed'             => 'MDT-String-Parsing fehlgeschlagen. Haben Sie wirklich einen MDT-String eingefügt?',
            'mdt_string_format_not_recognized'      => 'Das MDT-String-Format wurde nicht erkannt.',
            'cli_weakauras_parser_not_found'        => 'cli_weakauras_parser nicht installiert.',
            'invalid_mdt_string_exception'          => 'Ungültiger MDT-String: %s',
            'invalid_mdt_string'                    => 'Ungültiger MDT-String',
            'mdt_importer_not_configured_properly'  => 'MDT-Importer ist nicht richtig konfiguriert. Bitte kontaktieren Sie den Administrator zu diesem Problem.',
            'cannot_create_route_must_be_logged_in' => 'Sie müssen eingeloggt sein, um eine Route zu erstellen',
        ],
    ],
    'path'                        => [
        'error' => [
            'unable_to_save_path'   => 'Pfad konnte nicht gespeichert werden',
            'unable_to_delete_path' => 'Pfad konnte nicht gelöscht werden',
        ],
    ],
    'patreon'                     => [
        'flash' => [
            'unlink_successful'       => 'Ihr Patreon-Konto wurde erfolgreich entkoppelt.',
            'link_successful'         => 'Ihr Patreon wurde erfolgreich verknüpft. Danke!',
            'patreon_session_expired' => 'Ihre Patreon-Sitzung ist abgelaufen. Bitte versuchen Sie es erneut.',
            'session_expired'         => 'Ihre Sitzung ist abgelaufen. Bitte versuchen Sie es erneut.',
            'patreon_error_occurred'  => 'Auf der Patreon-Seite ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.',
            'internal_error_occurred' => 'Bei der Verarbeitung der Antwort von Patreon ist ein Fehler aufgetreten - sie scheint fehlerhaft zu sein. Der Fehler wurde protokolliert und wird bearbeitet. Bitte versuchen Sie es später erneut.',
        ],
    ],
    'profile'                     => [
        'flash' => [
            'email_already_in_use'             => 'Dieser Benutzername wird bereits verwendet.',
            'username_already_in_use'          => 'Dieser Benutzername wird bereits verwendet.',
            'profile_updated'                  => 'Profil aktualisiert',
            'unexpected_error_when_saving'     => 'Ein unerwarteter Fehler ist aufgetreten, als versucht wurde, Ihr Profil zu speichern',
            'privacy_settings_updated'         => 'Datenschutzeinstellungen aktualisiert',
            'password_changed'                 => 'Passwort geändert',
            'new_password_equals_old_password' => 'Neues Passwort entspricht dem alten Passwort',
            'new_passwords_do_not_match'       => 'Neue Passwörter stimmen nicht überein',
            'current_password_is_incorrect'    => 'Aktuelles Passwort ist falsch',
            'tag_created_successfully'         => 'Tag erfolgreich erstellt',
            'tag_already_exists'               => 'Dieses Tag existiert bereits',
            'admins_cannot_delete_themselves'  => 'Administratoren können sich nicht selbst löschen!',
            'account_deleted_successfully'     => 'Konto erfolgreich gelöscht.',
            'error_deleting_account'           => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.',
        ],
    ],
    'spell'                       => [
        'error' => [
            'unable_to_save_spell' => 'Zauber konnte nicht gespeichert werden',
        ],
        'flash' => [
            'spell_updated' => 'Zauber aktualisiert',
            'spell_created' => 'Zauber erstellt',
        ],
    ],
    'team'                        => [
        'flash' => [
            'team_updated'                        => 'Team aktualisiert',
            'team_created'                        => 'Team erstellt',
            'unable_to_find_team_for_invite_code' => 'Team für diesen Einladungscode konnte nicht gefunden werden',
            'invite_accept_success'               => 'Erfolg! Sie sind jetzt Mitglied des Teams %s.',
            'tag_created_successfully'            => 'Tag erfolgreich erstellt',
            'tag_already_exists'                  => 'Dieses Tag existiert bereits',
        ],
    ],
    'user'                        => [
        'flash' => [
            'user_is_now_an_admin'              => 'Benutzer :user ist jetzt ein Admin',
            'user_is_no_longer_an_admin'        => 'Benutzer :user ist kein Administrator mehr',
            'user_is_now_a_user'                => 'Benutzer :user ist jetzt ein Benutzer',
            'user_is_now_a_role'                => 'Benutzer :user ist jetzt ein :role',
            'account_deleted_successfully'      => 'Konto erfolgreich gelöscht.',
            'account_deletion_error'            => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.',
            'user_is_not_a_patron'              => 'Dieser Benutzer ist kein Patron.',
            'all_benefits_granted_successfully' => 'Alle Vorteile erfolgreich gewährt.',
            'error_granting_all_benefits'       => 'Beim Versuch, alle Vorteile zu gewähren, ist ein Fehler aufgetreten.',
        ],
    ],

];
