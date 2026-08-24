<?php

return [

    'admintools' => [
        'error' => [
            'mdt_string_parsing_failed'           => 'Analisi della stringa MDT fallita. Hai davvero incollato una stringa MDT?',
            'mdt_string_format_not_recognized'    => 'Il formato della stringa MDT non è stato riconosciuto.',
            'cli_weakauras_parser_not_found'      => 'cli_weakauras_parser non installato.',
            'invalid_mdt_string'                  => 'Stringa MDT non valida',
            'invalid_mdt_string_exception'        => 'Stringa MDT non valida: %s',
            'mdt_importer_not_configured'         => 'L\'importatore MDT non è configurato correttamente. Si prega di contattare l\'amministratore per questo problema.',
            'mdt_unable_to_find_npc_for_id'       => 'Impossibile trovare l\'NPC per id %d',
            'mdt_mismatched_health'               => 'NPC %s ha valori di salute non corrispondenti, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces'         => 'NPC %s ha forze nemiche non corrispondenti, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces_teeming' => 'NPC %s ha forze nemiche abbondanti non corrispondenti, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_count'          => 'NPC %s ha un conteggio nemico non corrispondente, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_type'           => 'NPC %s ha un tipo di nemico non corrispondente, MDT: %s, KG: %s',
            'mdt_invalid_category'                => 'Categoria non valida',
        ],
        'flash' => [
            'banned_ip_address_added'                => 'Indirizzo IP bannato con successo',
            'banned_ip_address_removed'              => 'Ban rimosso con successo',
            'message_banner_set_successfully'        => 'Banner del messaggio impostato con successo',
            'thumbnail_regenerate_result'            => 'Inviati :success lavori per :total percorsi. :failed falliti.',
            'combatlog_route_regenerate_result'      => 'Inviati :count lavori',
            'combatlog_criteria_reset'               => 'Tutti i conteggi dei criteri di analisi per oggi sono stati reimpostati.',
            'combatlog_criteria_thresholds_updated'  => 'Le soglie dei criteri di analisi sono state aggiornate.',
            'caches_dropped_successfully'            => 'Cache eliminate con successo',
            'caches_drop_queued'                     => 'L\'eliminazione della cache è stata accodata e verrà eseguita in background',
            'exception'                              => 'Eccezione lanciata nel pannello di amministrazione',
            'feature_toggle_activated'               => 'Funzione :feature ora attivata',
            'feature_toggle_deactivated'             => 'Funzione :feature ora disattivata',
            'feature_forgotten'                      => 'Funzione :feature dimenticata con successo',
            'mapping_version_upgrade_queued'         => 'Accodati :count percorsi per l\'aggiornamento dalla versione :version all\'ultima.',
            'mapping_version_upgrade_already_latest' => 'Questa versione di mappatura è già l\'ultima per il suo dungeon — nessun percorso è stato accodato.',
            'read_only_mode_disabled'                => 'Modalità sola lettura disattivata',
            'read_only_mode_enabled'                 => 'Modalità sola lettura attivata',
        ],
    ],
    'affix' => [
        'flash' => [
            'affix_created' => 'Affisso creato',
            'affix_updated' => 'Affisso aggiornato',
        ],
    ],
    'affixgroup' => [
        'flash' => [
            'affixgroup_created' => 'Gruppo di affissi creato',
            'affixgroup_updated' => 'Gruppo di affissi aggiornato',
            'affixgroup_deleted' => 'Gruppo di affissi eliminato',
        ],
    ],
    'apicombatlogroute' => [
        'error' => [
            'no_post_body' => 'Per questo percorso non è memorizzato alcun corpo della richiesta del percorso da combat log.',
        ],
    ],
    'apicombatlogrun' => [
        'error' => [
            'no_segments' => 'Nessun segmento di log di combattimento disponibile per questa run.',
        ],
    ],
    'apidungeonroute' => [
        'mdt_generate_error'  => 'Si è verificato un errore durante la generazione della tua stringa MDT: %s',
        'mdt_generate_no_lua' => 'L\'importatore MDT non è configurato correttamente. Si prega di contattare l\'amministratore per questo problema',
    ],
    'apiuserreport' => [
        'error' => [
            'unable_to_update_user_report' => 'Impossibile aggiornare il report utente',
            'unable_to_save_report'        => 'Impossibile salvare il report',
        ],
    ],
    'brushline' => [
        'error' => [
            'unable_to_save_brushline'   => 'Impossibile salvare la linea',
            'unable_to_delete_brushline' => 'Impossibile eliminare la linea',
        ],
    ],
    'arrow' => [
        'error' => [
            'unable_to_save_arrow'   => 'Impossibile salvare la freccia',
            'unable_to_delete_arrow' => 'Impossibile eliminare la freccia',
        ],
    ],
    'dungeon' => [
        'flash' => [
            'dungeon_created' => 'Dungeon creato',
            'dungeon_updated' => 'Dungeon aggiornato',
        ],
    ],
    'dungeonroute' => [
        'unable_to_save' => 'Impossibile salvare il percorso',
        'flash'          => [
            'route_cloned_successfully' => 'Percorso clonato con successo',
            'route_updated'             => 'Percorso aggiornato',
            'route_created'             => 'Percorso creato',
            'upgrade_draft_created'     => 'È stata creata una bozza di aggiornamento. Sistemala qui: il percorso originale continua a mostrare il suo contenuto precedente finché non applichi le tue modifiche.',
            'upgrade_applied'           => 'L\'aggiornamento è stato applicato al tuo percorso.',
            'upgrade_discarded'         => 'La bozza di aggiornamento è stata scartata.',
        ],
    ],
    'dungeonroutediscover' => [
        'popular' => 'Percorsi popolari',
        'new'     => 'Nuovo',
        'season'  => [
            'popular' => '%s percorsi popolari',
            'new'     => '%s nuovi percorsi',
        ],
        'dungeon' => [
            'popular' => '%s percorsi popolari',
            'new'     => '%s nuovi percorsi',
        ],
    ],
    'dungeonspeedrunrequirednpcs' => [
        'no_linked_npc' => 'Nessun NPC collegato',
        'flash'         => [
            'npc_added_successfully'   => 'NPC aggiunto con successo',
            'npc_deleted_successfully' => 'NPC rimosso con successo',
        ],
    ],
    'expansion' => [
        'flash' => [
            'unable_to_save_expansion' => 'Impossibile salvare l\'espansione',
            'expansion_updated'        => 'Espansione aggiornata',
            'expansion_created'        => 'Espansione creata',
        ],
    ],
    'generic' => [
        'error' => [
            'floor_not_found_in_dungeon' => 'Piano non parte del dungeon',
            'not_found'                  => 'Non trovato',
        ],
    ],
    'killzone' => [
        'error' => [
            'facade_location_not_convertible' => 'Impossibile posizionare il pull qui - questa posizione non appartiene a nessun piano di questo dungeon',
            'unable_to_delete_pull'           => 'Impossibile eliminare il pull',
        ],
    ],
    'oauthlogin' => [
        'flash' => [
            'registered_successfully' => 'Registrazione avvenuta con successo. Goditi il sito!',
            'user_exists'             => 'Esiste già un utente con il nome utente %s. Ti sei già registrato in precedenza?',
            'email_exists'            => 'Esiste già un utente con l\'indirizzo e-mail %s. Ti sei già registrato in precedenza?',
            'permission_denied'       => 'Impossibile registrarsi - la richiesta è stata negata. Per favore riprova.',
            'read_only_mode_enabled'  => 'La modalità di sola lettura è abilitata. Non puoi registrarti in questo momento.',
        ],
    ],
    'register' => [
        'flash' => [
            'registered_successfully' => 'Registrazione avvenuta con successo. Goditi il sito!',
        ],
        'legal_agreed_required' => 'Devi accettare i nostri termini legali per registrarti.',
        'legal_agreed_accepted' => 'Devi accettare i nostri termini legali per registrarti.',
    ],
    'mappingversion' => [
        'created_successfully'      => 'Aggiunta nuova versione di mappatura!',
        'created_bare_successfully' => 'Aggiunta nuova versione di mappatura base!',
        'deleted_successfully'      => 'Versione di mappatura eliminata con successo',
    ],
    'mdtimport' => [
        'unknown_dungeon' => 'Dungeon sconosciuto',
        'error'           => [
            'mdt_string_parsing_failed'             => 'Parsing della stringa MDT fallito. Hai davvero incollato una stringa MDT?',
            'mdt_string_format_not_recognized'      => 'Il formato della stringa MDT non è stato riconosciuto.',
            'cli_weakauras_parser_not_found'        => 'cli_weakauras_parser non installato.',
            'invalid_mdt_string_exception'          => 'Stringa MDT non valida: %s',
            'invalid_mdt_string'                    => 'Stringa MDT non valida',
            'mdt_importer_not_configured_properly'  => 'L\'importatore MDT non è configurato correttamente. Si prega di contattare l\'amministratore per questo problema.',
            'cannot_create_route_must_be_logged_in' => 'Devi essere loggato per creare un percorso',
        ],
    ],
    'path' => [
        'error' => [
            'unable_to_save_path'   => 'Impossibile salvare il percorso',
            'unable_to_delete_path' => 'Impossibile eliminare il percorso',
        ],
    ],
    'patreon' => [
        'flash' => [
            'unlink_successful'       => 'Il tuo account Patreon è stato scollegato con successo.',
            'link_successful'         => 'Il tuo Patreon è stato collegato con successo. Grazie!',
            'patreon_session_expired' => 'La tua sessione Patreon è scaduta. Si prega di riprovare.',
            'session_expired'         => 'La tua sessione è scaduta. Si prega di riprovare.',
            'patreon_error_occurred'  => 'Si è verificato un errore da parte di Patreon. Si prega di riprovare più tardi.',
            'internal_error_occurred' => 'Si è verificato un errore durante l\'elaborazione della risposta di Patreon - sembra essere malformata. L\'errore è stato registrato e verrà gestito. Si prega di riprovare più tardi.',
        ],
    ],
    'profile' => [
        'flash' => [
            'email_already_in_use'             => 'Quel nome utente è già in uso.',
            'username_already_in_use'          => 'Quel nome utente è già in uso.',
            'profile_updated'                  => 'Profilo aggiornato',
            'unexpected_error_when_saving'     => 'Si è verificato un errore inaspettato provando a salvare il tuo profilo',
            'privacy_settings_updated'         => 'Impostazioni sulla privacy aggiornate',
            'creator_profile_updated'          => 'Profilo creatore aggiornato',
            'password_changed'                 => 'Password cambiata',
            'new_password_equals_old_password' => 'La nuova password è uguale a quella vecchia',
            'new_passwords_do_not_match'       => 'Le nuove password non corrispondono',
            'current_password_is_incorrect'    => 'La password attuale è errata',
            'tag_created_successfully'         => 'Tag creato con successo',
            'tag_already_exists'               => 'Questo tag esiste già',
            'admins_cannot_delete_themselves'  => 'Gli amministratori non possono eliminare se stessi!',
            'account_deleted_successfully'     => 'Account eliminato con successo.',
            'error_deleting_account'           => 'Si è verificato un errore. Si prega di riprovare.',
        ],
        'error' => [
            'add_ad_free_giveaway_limit_reached'        => 'Impossibile aggiungere altri giveaway senza pubblicità. Limite raggiunto.',
            'add_ad_free_giveaway_already_ad_free'      => 'Impossibile aggiungere giveaway senza pubblicità, l\'utente è già senza pubblicità tramite il proprio abbonamento Patreon.',
            'add_ad_free_giveaway_already_has_giveaway' => 'Impossibile aggiungere giveaway senza pubblicità, l\'utente è già senza pubblicità tramite un giveaway esistente.',
            'remove_ad_free_giveaway_not_found'         => 'Impossibile rimuovere il giveaway senza pubblicità - l\'utente al momento non ne ha nessuno.',
            'remove_ad_free_giveaway_not_yours'         => 'Impossibile rimuovere giveaway senza pubblicità che non sono stati originariamente assegnati da te.',
        ],
    ],
    'season' => [
        'flash' => [
            'season_created' => 'Stagione creata',
            'season_updated' => 'Stagione aggiornata',
        ],
    ],
    'spell' => [
        'error' => [
            'unable_to_save_spell' => 'Impossibile salvare l\'incantesimo',
        ],
        'flash' => [
            'spell_updated' => 'Incantesimo aggiornato',
            'spell_created' => 'Incantesimo creato',
        ],
    ],
    'team' => [
        'flash' => [
            'team_updated'                        => 'Team aggiornato',
            'team_created'                        => 'Team creato',
            'unable_to_find_team_for_invite_code' => 'Impossibile trovare un team associato a questo codice di invito',
            'invite_accept_success'               => 'Successo! Ora sei un membro del team %s.',
            'tag_created_successfully'            => 'Tag creato con successo',
            'tag_already_exists'                  => 'Questo tag esiste già',
        ],
    ],
    'user' => [
        'flash' => [
            'user_is_now_an_admin'              => 'L\'utente :user è ora un amministratore',
            'user_is_no_longer_an_admin'        => 'L\'utente :user non è più un amministratore',
            'user_is_now_a_user'                => 'L\'utente :user è ora un utente',
            'user_is_now_a_role'                => 'L\'utente :user è ora un :role',
            'account_deleted_successfully'      => 'Account eliminato con successo.',
            'account_deletion_error'            => 'Si è verificato un errore. Si prega di riprovare.',
            'user_is_not_a_patron'              => 'Questo utente non è un Patron.',
            'all_benefits_granted_successfully' => 'Tutti i benefici concessi con successo.',
            'error_granting_all_benefits'       => 'Si è verificato un errore durante il tentativo di concedere tutti i benefici.',
        ],
    ],

    'admin' => [
        'dungeonroute' => [
            'flash' => [
                'updated' => 'Percorso del dungeon aggiornato con successo.',
                'deleted' => 'Percorso del dungeon eliminato con successo.',
                'claimed' => 'Percorso del dungeon rivendicato con successo. Ora ne sei il proprietario.',
            ],
        ],
    ],

];
