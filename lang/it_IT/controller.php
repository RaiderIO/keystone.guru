<?php

return [
    'admintools' => [
        'error' => [
            'cli_weakauras_parser_not_found'      => 'cli_weakauras_parser non installato.',
            'invalid_mdt_string'                  => 'Stringa MDT non valida',
            'invalid_mdt_string_exception'        => 'Stringa MDT non valida: %s',
            'mdt_importer_not_configured'         => 'L\'importatore MDT non è configurato correttamente. Si prega di contattare l\'amministratore per questo problema.',
            'mdt_invalid_category'                => 'Categoria non valida',
            'mdt_mismatched_enemy_count'          => 'NPC %s ha un conteggio nemico non corrispondente, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces'         => 'NPC %s ha forze nemiche non corrispondenti, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces_teeming' => 'NPC %s ha forze nemiche abbondanti non corrispondenti, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_type'           => 'NPC %s ha un tipo di nemico non corrispondente, MDT: %s, KG: %s',
            'mdt_mismatched_health'               => 'NPC %s ha valori di salute non corrispondenti, MDT: %s, KG: %s',
            'mdt_string_format_not_recognized'    => 'Il formato della stringa MDT non è stato riconosciuto.',
            'mdt_string_parsing_failed'           => 'Analisi della stringa MDT fallita. Hai davvero incollato una stringa MDT?',
            'mdt_unable_to_find_npc_for_id'       => 'Impossibile trovare l\'NPC per id %d',
        ],
        'flash' => [
            'caches_dropped_successfully'     => 'Cache eliminate con successo',
            'exception'                       => 'Eccezione lanciata nel pannello di amministrazione',
            'feature_forgotten'               => 'Funzione :feature dimenticata con successo',
            'feature_toggle_activated'        => 'Funzione :feature ora attivata',
            'feature_toggle_deactivated'      => 'Funzione :feature ora disattivata',
            'message_banner_set_successfully' => 'Banner del messaggio impostato con successo',
            'read_only_mode_disabled'         => 'Modalità sola lettura disattivata',
            'read_only_mode_enabled'          => 'Modalità sola lettura attivata',
            'releases_exported'               => 'Versioni esportate',
            'thumbnail_regenerate_result'     => 'Inviati :success lavori per :total percorsi. :failed falliti.',
        ],
    ],
    'apidungeonroute' => [
        'mdt_generate_error'  => 'Si è verificato un errore durante la generazione della tua stringa MDT: %s',
        'mdt_generate_no_lua' => 'L\'importatore MDT non è configurato correttamente. Si prega di contattare l\'amministratore per questo problema',
    ],
    'apiuserreport' => [
        'error' => [
            'unable_to_save_report'        => 'Impossibile salvare il report',
            'unable_to_update_user_report' => 'Impossibile aggiornare il report utente',
        ],
    ],
    'brushline' => [
        'error' => [
            'unable_to_delete_brushline' => 'Impossibile eliminare la linea',
            'unable_to_save_brushline'   => 'Impossibile salvare la linea',
        ],
    ],
    'dungeon' => [
        'flash' => [
            'dungeon_created' => 'Dungeon creato',
            'dungeon_updated' => 'Dungeon aggiornato',
        ],
    ],
    'dungeonroute' => [
        'flash' => [
            'route_cloned_successfully' => 'Percorso clonato con successo',
            'route_created'             => 'Percorso creato',
            'route_updated'             => 'Percorso aggiornato',
        ],
        'unable_to_save' => 'Impossibile salvare il percorso',
    ],
    'dungeonroutediscover' => [
        'dungeon' => [
            'new'               => '%s nuovi percorsi',
            'next_week_affixes' => '%s la prossima settimana',
            'popular'           => '%s percorsi popolari',
            'this_week_affixes' => '%s questa settimana',
        ],
        'new'               => 'Nuovo',
        'next_week_affixes' => 'Affissi della prossima settimana',
        'popular'           => 'Percorsi popolari',
        'season'            => [
            'new'               => '%s nuovi percorsi',
            'next_week_affixes' => '%s prossima settimana',
            'popular'           => '%s percorsi popolari',
            'this_week_affixes' => '%s questa settimana',
        ],
        'this_week_affixes' => 'Affissi di questa settimana',
    ],
    'dungeonspeedrunrequirednpcs' => [
        'flash' => [
            'npc_added_successfully'   => 'NPC aggiunto con successo',
            'npc_deleted_successfully' => 'NPC rimosso con successo',
        ],
        'no_linked_npc' => 'Nessun NPC collegato',
    ],
    'expansion' => [
        'flash' => [
            'expansion_created'        => 'Espansione creata',
            'expansion_updated'        => 'Espansione aggiornata',
            'unable_to_save_expansion' => 'Impossibile salvare l\'espansione',
        ],
    ],
    'generic' => [
        'error' => [
            'floor_not_found_in_dungeon' => 'Piano non parte del dungeon',
            'not_found'                  => 'Non trovato',
        ],
    ],
    'mappingversion' => [
        'created_bare_successfully' => 'Aggiunta nuova versione di mappatura base!',
        'created_successfully'      => 'Aggiunta nuova versione di mappatura!',
        'deleted_successfully'      => 'Versione di mappatura eliminata con successo',
    ],
    'mdtimport' => [
        'error' => [
            'cannot_create_route_must_be_logged_in' => 'Devi essere loggato per creare un percorso',
            'cli_weakauras_parser_not_found'        => 'cli_weakauras_parser non installato.',
            'invalid_mdt_string'                    => 'Stringa MDT non valida',
            'invalid_mdt_string_exception'          => 'Stringa MDT non valida: %s',
            'mdt_importer_not_configured_properly'  => 'L\'importatore MDT non è configurato correttamente. Si prega di contattare l\'amministratore per questo problema.',
            'mdt_string_format_not_recognized'      => 'Il formato della stringa MDT non è stato riconosciuto.',
            'mdt_string_parsing_failed'             => 'Parsing della stringa MDT fallito. Hai davvero incollato una stringa MDT?',
        ],
        'unknown_dungeon' => 'Dungeon sconosciuto',
    ],
    'oauthlogin' => [
        'flash' => [
            'email_exists'            => 'Esiste già un utente con l\'indirizzo e-mail %s. Ti sei già registrato in precedenza?',
            'permission_denied'       => 'Impossibile registrarsi - la richiesta è stata negata. Per favore riprova.',
            'read_only_mode_enabled'  => 'La modalità di sola lettura è abilitata. Non puoi registrarti in questo momento.',
            'registered_successfully' => 'Registrazione avvenuta con successo. Goditi il sito!',
            'user_exists'             => 'Esiste già un utente con il nome utente %s. Ti sei già registrato in precedenza?',
        ],
    ],
    'path' => [
        'error' => [
            'unable_to_delete_path' => 'Impossibile eliminare il percorso',
            'unable_to_save_path'   => 'Impossibile salvare il percorso',
        ],
    ],
    'patreon' => [
        'flash' => [
            'internal_error_occurred' => 'Si è verificato un errore durante l\'elaborazione della risposta di Patreon - sembra essere malformata. L\'errore è stato registrato e verrà gestito. Si prega di riprovare più tardi.',
            'link_successful'         => 'Il tuo Patreon è stato collegato con successo. Grazie!',
            'patreon_error_occurred'  => 'Si è verificato un errore da parte di Patreon. Si prega di riprovare più tardi.',
            'patreon_session_expired' => 'La tua sessione Patreon è scaduta. Si prega di riprovare.',
            'session_expired'         => 'La tua sessione è scaduta. Si prega di riprovare.',
            'unlink_successful'       => 'Il tuo account Patreon è stato scollegato con successo.',
        ],
    ],
    'profile' => [
        'flash' => [
            'account_deleted_successfully'     => 'Account eliminato con successo.',
            'admins_cannot_delete_themselves'  => 'Gli amministratori non possono eliminare se stessi!',
            'current_password_is_incorrect'    => 'La password attuale è errata',
            'email_already_in_use'             => 'Quel nome utente è già in uso.',
            'error_deleting_account'           => 'Si è verificato un errore. Si prega di riprovare.',
            'new_password_equals_old_password' => 'La nuova password è uguale a quella vecchia',
            'new_passwords_do_not_match'       => 'Le nuove password non corrispondono',
            'password_changed'                 => 'Password cambiata',
            'privacy_settings_updated'         => 'Impostazioni sulla privacy aggiornate',
            'profile_updated'                  => 'Profilo aggiornato',
            'tag_already_exists'               => 'Questo tag esiste già',
            'tag_created_successfully'         => 'Tag creato con successo',
            'unexpected_error_when_saving'     => 'Si è verificato un errore inaspettato provando a salvare il tuo profilo',
            'username_already_in_use'          => 'Quel nome utente è già in uso.',
        ],
    ],
    'register' => [
        'flash' => [
            'registered_successfully' => 'Registrazione avvenuta con successo. Goditi il sito!',
        ],
        'legal_agreed_accepted' => 'Devi accettare i nostri termini legali per registrarti.',
        'legal_agreed_required' => 'Devi accettare i nostri termini legali per registrarti.',
    ],
    'release' => [
        'error' => [
            'unable_to_save_release' => 'Impossibile salvare la release',
        ],
        'flash' => [
            'github_exception' => 'Si è verificato un errore comunicando con Github: :message',
            'release_created'  => 'Release creata',
            'release_updated'  => 'Release aggiornata',
        ],
    ],
    'spell' => [
        'error' => [
            'unable_to_save_spell' => 'Impossibile salvare l\'incantesimo',
        ],
        'flash' => [
            'spell_created' => 'Incantesimo creato',
            'spell_updated' => 'Incantesimo aggiornato',
        ],
    ],
    'team' => [
        'flash' => [
            'invite_accept_success'               => 'Successo! Ora sei un membro del team %s.',
            'tag_already_exists'                  => 'Questo tag esiste già',
            'tag_created_successfully'            => 'Tag creato con successo',
            'team_created'                        => 'Team creato',
            'team_updated'                        => 'Team aggiornato',
            'unable_to_find_team_for_invite_code' => 'Impossibile trovare un team associato a questo codice di invito',
        ],
    ],
    'user' => [
        'flash' => [
            'account_deleted_successfully'      => 'Account eliminato con successo.',
            'account_deletion_error'            => 'Si è verificato un errore. Si prega di riprovare.',
            'all_benefits_granted_successfully' => 'Tutti i benefici concessi con successo.',
            'error_granting_all_benefits'       => 'Si è verificato un errore durante il tentativo di concedere tutti i benefici.',
            'user_is_no_longer_an_admin'        => 'L\'utente :user non è più un amministratore',
            'user_is_not_a_patron'              => 'Questo utente non è un Patron.',
            'user_is_now_a_role'                => 'L\'utente :user è ora un :role',
            'user_is_now_a_user'                => 'L\'utente :user è ora un utente',
            'user_is_now_an_admin'              => 'L\'utente :user è ora un amministratore',
        ],
    ],
];
