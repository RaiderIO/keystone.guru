<?php

return [
    'admintools' => [
        'error' => [
            'cli_weakauras_parser_not_found'      => 'cli_weakauras_parser nicht installiert.',
            'invalid_mdt_string'                  => 'Ungültiger MDT-String',
            'invalid_mdt_string_exception'        => 'Ungültiger MDT-String: %s',
            'mdt_importer_not_configured'         => 'MDT-Importer ist nicht richtig konfiguriert. Bitte kontaktieren Sie den Administrator über dieses Problem.',
            'mdt_invalid_category'                => 'Ungültige Kategorie',
            'mdt_mismatched_enemy_count'          => 'NPC %s hat eine nicht übereinstimmende Feindanzahl, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces'         => 'NPC %s hat nicht übereinstimmende Feindkräfte, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces_teeming' => 'NPC %s hat nicht übereinstimmende wimmelnde Feindkräfte, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_type'           => 'NPC %s hat nicht übereinstimmenden Feindtyp, MDT: %s, KG: %s',
            'mdt_mismatched_health'               => 'NPC %s hat nicht übereinstimmende Gesundheitswerte, MDT: %s, KG: %s',
            'mdt_string_format_not_recognized'    => 'Das MDT-String-Format wurde nicht erkannt.',
            'mdt_string_parsing_failed'           => 'MDT-String-Parsing fehlgeschlagen. Haben Sie wirklich einen MDT-String eingefügt?',
            'mdt_unable_to_find_npc_for_id'       => 'Konnte NPC für ID %d nicht finden',
        ],
        'flash' => [
            'caches_dropped_successfully'     => 'Caches erfolgreich gelöscht',
            'exception'                       => 'Ausnahme im Admin-Panel ausgelöst',
            'feature_forgotten'               => 'Feature :feature erfolgreich vergessen',
            'feature_toggle_activated'        => 'Feature :feature ist jetzt aktiviert',
            'feature_toggle_deactivated'      => 'Feature :feature ist jetzt deaktiviert',
            'message_banner_set_successfully' => 'Nachrichtenbanner erfolgreich gesetzt',
            'read_only_mode_disabled'         => 'Schreibgeschützter Modus deaktiviert',
            'read_only_mode_enabled'          => 'Schreibgeschützter Modus aktiviert',
            'releases_exported'               => 'Releases exportiert',
            'thumbnail_regenerate_result'     => ':success Jobs für :total Routen versendet. :failed fehlgeschlagen.',
        ],
    ],
    'apidungeonroute' => [
        'mdt_generate_error'  => 'Ein Fehler ist beim Generieren Ihres MDT-Strings aufgetreten: %s',
        'mdt_generate_no_lua' => 'MDT-Importer ist nicht richtig konfiguriert. Bitte kontaktieren Sie den Administrator über dieses Problem',
    ],
    'apiuserreport' => [
        'error' => [
            'unable_to_save_report'        => 'Bericht konnte nicht gespeichert werden',
            'unable_to_update_user_report' => 'Benutzerbericht konnte nicht aktualisiert werden',
        ],
    ],
    'brushline' => [
        'error' => [
            'unable_to_delete_brushline' => 'Linie konnte nicht gelöscht werden',
            'unable_to_save_brushline'   => 'Linie konnte nicht gespeichert werden',
        ],
    ],
    'dungeon' => [
        'flash' => [
            'dungeon_created' => 'Dungeon erstellt',
            'dungeon_updated' => 'Dungeon aktualisiert',
        ],
    ],
    'dungeonroute' => [
        'flash'          => [
            'route_cloned_successfully' => 'Route erfolgreich geklont',
            'route_created'             => 'Route erstellt',
            'route_updated'             => 'Route aktualisiert',
        ],
        'unable_to_save' => 'Route konnte nicht gespeichert werden',
    ],
    'dungeonroutediscover' => [
        'dungeon' => [
            'new'               => '%s neue Routen',
            'next_week_affixes' => '%s nächste Woche',
            'popular'           => '%s beliebte Routen',
            'this_week_affixes' => '%s diese Woche',
        ],
        'new'     => 'Neu',
        'next_week_affixes' => 'Affixe der nächsten Woche',
        'popular' => 'Beliebte Routen',
        'season' => [
            'new'               => '%s neue Routen',
            'next_week_affixes' => '%s nächste Woche',
            'popular'           => '%s beliebte Routen',
            'this_week_affixes' => '%s diese Woche',
        ],
        'this_week_affixes' => 'Affixe dieser Woche',
    ],
    'dungeonspeedrunrequirednpcs' => [
        'flash'         => [
            'npc_added_successfully'   => 'NPC erfolgreich hinzugefügt',
            'npc_deleted_successfully' => 'NPC erfolgreich entfernt',
        ],
        'no_linked_npc' => 'Kein verknüpfter NPC',
    ],
    'expansion' => [
        'flash' => [
            'expansion_created'        => 'Erweiterung erstellt',
            'expansion_updated'        => 'Erweiterung aktualisiert',
            'unable_to_save_expansion' => 'Erweiterung konnte nicht gespeichert werden',
        ],
    ],
    'generic' => [
        'error' => [
            'floor_not_found_in_dungeon' => 'Etage nicht Teil des Dungeons',
            'not_found'                  => 'Nicht gefunden',
        ],
    ],
    'mappingversion' => [
        'created_bare_successfully' => 'Neue einfache Mapping-Version hinzugefügt!',
        'created_successfully'      => 'Neue Mapping-Version hinzugefügt!',
        'deleted_successfully'      => 'Mapping-Version erfolgreich gelöscht',
    ],
    'mdtimport' => [
        'error'           => [
            'cannot_create_route_must_be_logged_in' => 'Sie müssen eingeloggt sein, um eine Route zu erstellen',
            'cli_weakauras_parser_not_found'        => 'cli_weakauras_parser nicht installiert.',
            'invalid_mdt_string'                    => 'Ungültiger MDT-String',
            'invalid_mdt_string_exception'          => 'Ungültiger MDT-String: %s',
            'mdt_importer_not_configured_properly'  => 'MDT-Importer ist nicht richtig konfiguriert. Bitte kontaktieren Sie den Administrator zu diesem Problem.',
            'mdt_string_format_not_recognized'      => 'Das MDT-String-Format wurde nicht erkannt.',
            'mdt_string_parsing_failed'             => 'MDT-String-Parsing fehlgeschlagen. Haben Sie wirklich einen MDT-String eingefügt?',
        ],
        'unknown_dungeon' => 'Unbekannter Dungeon',
    ],
    'oauthlogin' => [
        'flash' => [
            'email_exists'            => 'Es gibt bereits einen Benutzer mit der E-Mail-Adresse %s. Haben Sie sich schon vorher registriert?',
            'permission_denied'       => 'Registrierung nicht möglich - die Anfrage wurde abgelehnt. Bitte versuchen Sie es erneut.',
            'read_only_mode_enabled'  => 'Schreibgeschützter Modus ist aktiviert. Sie können sich derzeit nicht registrieren.',
            'registered_successfully' => 'Erfolgreich registriert. Viel Spaß auf der Website!',
            'user_exists'             => 'Es gibt bereits einen Benutzer mit dem Benutzernamen %s. Haben Sie sich schon vorher registriert?',
        ],
    ],
    'path' => [
        'error' => [
            'unable_to_delete_path' => 'Pfad konnte nicht gelöscht werden',
            'unable_to_save_path'   => 'Pfad konnte nicht gespeichert werden',
        ],
    ],
    'patreon' => [
        'flash' => [
            'internal_error_occurred' => 'Bei der Verarbeitung der Antwort von Patreon ist ein Fehler aufgetreten - sie scheint fehlerhaft zu sein. Der Fehler wurde protokolliert und wird bearbeitet. Bitte versuchen Sie es später erneut.',
            'link_successful'         => 'Ihr Patreon wurde erfolgreich verknüpft. Danke!',
            'patreon_error_occurred'  => 'Auf der Patreon-Seite ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.',
            'patreon_session_expired' => 'Ihre Patreon-Sitzung ist abgelaufen. Bitte versuchen Sie es erneut.',
            'session_expired'         => 'Ihre Sitzung ist abgelaufen. Bitte versuchen Sie es erneut.',
            'unlink_successful'       => 'Ihr Patreon-Konto wurde erfolgreich entkoppelt.',
        ],
    ],
    'profile' => [
        'flash' => [
            'account_deleted_successfully'     => 'Konto erfolgreich gelöscht.',
            'admins_cannot_delete_themselves'  => 'Administratoren können sich nicht selbst löschen!',
            'current_password_is_incorrect'    => 'Aktuelles Passwort ist falsch',
            'email_already_in_use'             => 'Dieser Benutzername wird bereits verwendet.',
            'error_deleting_account'           => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.',
            'new_password_equals_old_password' => 'Neues Passwort entspricht dem alten Passwort',
            'new_passwords_do_not_match'       => 'Neue Passwörter stimmen nicht überein',
            'password_changed'                 => 'Passwort geändert',
            'privacy_settings_updated'         => 'Datenschutzeinstellungen aktualisiert',
            'profile_updated'                  => 'Profil aktualisiert',
            'tag_already_exists'               => 'Dieses Tag existiert bereits',
            'tag_created_successfully'         => 'Tag erfolgreich erstellt',
            'unexpected_error_when_saving'     => 'Ein unerwarteter Fehler ist aufgetreten, als versucht wurde, Ihr Profil zu speichern',
            'username_already_in_use'          => 'Dieser Benutzername wird bereits verwendet.',
        ],
    ],
    'register' => [
        'flash'                 => [
            'registered_successfully' => 'Erfolgreich registriert. Viel Spaß auf der Website!',
        ],
        'legal_agreed_accepted' => 'Sie müssen unseren rechtlichen Bedingungen zustimmen, um sich zu registrieren.',
        'legal_agreed_required' => 'Sie müssen unseren rechtlichen Bedingungen zustimmen, um sich zu registrieren.',
    ],
    'release' => [
        'error' => [
            'unable_to_save_release' => 'Release konnte nicht gespeichert werden',
        ],
        'flash' => [
            'github_exception' => 'Es ist ein Fehler bei der Kommunikation mit Github aufgetreten: :message',
            'release_created'  => 'Release erstellt',
            'release_updated'  => 'Release aktualisiert',
        ],
    ],
    'spell' => [
        'error' => [
            'unable_to_save_spell' => 'Zauber konnte nicht gespeichert werden',
        ],
        'flash' => [
            'spell_created' => 'Zauber erstellt',
            'spell_updated' => 'Zauber aktualisiert',
        ],
    ],
    'team' => [
        'flash' => [
            'invite_accept_success'               => 'Erfolg! Sie sind jetzt Mitglied des Teams %s.',
            'tag_already_exists'                  => 'Dieses Tag existiert bereits',
            'tag_created_successfully'            => 'Tag erfolgreich erstellt',
            'team_created'                        => 'Team erstellt',
            'team_updated'                        => 'Team aktualisiert',
            'unable_to_find_team_for_invite_code' => 'Team für diesen Einladungscode konnte nicht gefunden werden',
        ],
    ],
    'user' => [
        'flash' => [
            'account_deleted_successfully'      => 'Konto erfolgreich gelöscht.',
            'account_deletion_error'            => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.',
            'all_benefits_granted_successfully' => 'Alle Vorteile erfolgreich gewährt.',
            'error_granting_all_benefits'       => 'Beim Versuch, alle Vorteile zu gewähren, ist ein Fehler aufgetreten.',
            'user_is_no_longer_an_admin'        => 'Benutzer :user ist kein Administrator mehr',
            'user_is_not_a_patron'              => 'Dieser Benutzer ist kein Patron.',
            'user_is_now_a_role'                => 'Benutzer :user ist jetzt ein :role',
            'user_is_now_a_user'                => 'Benutzer :user ist jetzt ein Benutzer',
            'user_is_now_an_admin'              => 'Benutzer :user ist jetzt ein Admin',
        ],
    ],
];
