<?php

return [

    'admintools' => [
        'error' => [
            'mdt_string_parsing_failed'        => 'Échec de l\'analyse de la chaîne MDT. Avez-vous vraiment collé une chaîne MDT?',
            'mdt_string_format_not_recognized' => 'Le format de la chaîne MDT n\'a pas été reconnu.',
            'cli_weakauras_parser_not_found'      => 'cli_weakauras_parser non installé.',
            'invalid_mdt_string'                  => 'Chaîne MDT invalide',
            'invalid_mdt_string_exception'        => 'Chaîne MDT invalide: %s',
            'mdt_importer_not_configured'         => 'L\'importateur MDT n\'est pas configuré correctement. Veuillez contacter l\'administrateur à propos de ce problème.',
            'mdt_unable_to_find_npc_for_id'    => 'Impossible de trouver le PNJ pour l\'identifiant %d',
            'mdt_mismatched_health'            => 'Le PNJ %s a des valeurs de santé non correspondantes, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces'         => 'Le PNJ %s a des forces ennemies non correspondantes, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces_teeming' => 'Le PNJ %s a des forces ennemies grouillantes non correspondantes, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_count'       => 'Le PNJ %s a un nombre d\'ennemis non correspondant, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_type'           => 'Le PNJ %s a un type d\'ennemi non correspondant, MDT: %s, KG: %s',
            'mdt_invalid_category'             => 'Catégorie invalide',
        ],
        'flash' => [
            'message_banner_set_successfully' => 'Bannière de message définie avec succès',
            'thumbnail_regenerate_result'     => ':success tâches envoyées pour :total itinéraires. :failed a échoué.',
            'caches_dropped_successfully'     => 'Caches supprimées avec succès',
            'releases_exported'               => 'Versions exportées',
            'exception'                       => 'Exception lancée dans le panneau d\'administration',
            'feature_toggle_activated'        => 'La fonctionnalité :feature est maintenant activée',
            'feature_toggle_deactivated'      => 'La fonctionnalité :feature est maintenant désactivée',
            'feature_forgotten'               => 'Fonctionnalité :feature oubliée avec succès',
            'read_only_mode_disabled'         => 'Mode lecture seule désactivé',
            'read_only_mode_enabled'          => 'Mode lecture seule activé',
        ],
    ],
    'apidungeonroute' => [
        'mdt_generate_error'  => 'Une erreur s\'est produite lors de la génération de votre chaîne MDT: %s',
        'mdt_generate_no_lua' => 'L\'importateur MDT n\'est pas configuré correctement. Veuillez contacter l\'administrateur à propos de ce problème',
    ],
    'apiuserreport' => [
        'error' => [
            'unable_to_update_user_report' => 'Impossible de mettre à jour le rapport utilisateur',
            'unable_to_save_report'        => 'Impossible de sauvegarder le rapport',
        ],
    ],
    'brushline' => [
        'error' => [
            'unable_to_save_brushline'   => 'Impossible de sauvegarder la ligne',
            'unable_to_delete_brushline' => 'Impossible de supprimer la ligne',
        ],
    ],
    'dungeon' => [
        'flash' => [
            'dungeon_created' => 'Donjon créé',
            'dungeon_updated' => 'Donjon mis à jour',
        ],
    ],
    'dungeonroute' => [
        'unable_to_save' => 'Impossible de sauvegarder l\'itinéraire',
        'flash'          => [
            'route_cloned_successfully' => 'Itinéraire cloné avec succès',
            'route_updated' => 'Itinéraire mis à jour',
            'route_created'             => 'Itinéraire créé',
        ],
    ],
    'dungeonroutediscover' => [
        'popular'           => 'Itinéraires populaires',
        'this_week_affixes' => 'Les affixes de cette semaine',
        'next_week_affixes' => 'Les affixes de la semaine prochaine',
        'new'               => 'Nouveau',
        'season'            => [
            'popular'           => '%s itinéraires populaires',
            'this_week_affixes' => '%s cette semaine',
            'next_week_affixes' => '%s la semaine prochaine',
            'new'               => '%s nouveaux itinéraires',
        ],
        'dungeon'           => [
            'popular'           => '%s itinéraires populaires',
            'this_week_affixes' => '%s cette semaine',
            'next_week_affixes' => '%s la semaine prochaine',
            'new'               => '%s nouveaux itinéraires',
        ],
    ],
    'dungeonspeedrunrequirednpcs' => [
        'no_linked_npc' => 'Aucun PNJ lié',
        'flash'         => [
            'npc_added_successfully'   => 'PNJ ajouté avec succès',
            'npc_deleted_successfully' => 'PNJ supprimé avec succès',
        ],
    ],
    'expansion' => [
        'flash' => [
            'unable_to_save_expansion' => 'Impossible de sauvegarder l\'extension',
            'expansion_updated'        => 'Extension mise à jour',
            'expansion_created'        => 'Extension créée',
        ],
    ],
    'generic' => [
        'error' => [
            'floor_not_found_in_dungeon' => 'Étage non inclus dans le donjon',
            'not_found'                  => 'Non trouvé',
        ],
    ],
    'oauthlogin' => [
        'flash' => [
            'registered_successfully' => 'Inscription réussie. Profitez du site!',
            'user_exists'             => 'Il y a déjà un utilisateur avec le nom d\'utilisateur %s. Vous êtes-vous déjà inscrit auparavant?',
            'email_exists'            => 'Il y a déjà un utilisateur avec l\'adresse e-mail %s. Vous êtes-vous déjà inscrit auparavant?',
            'permission_denied'       => 'Impossible de s\'inscrire - la demande a été refusée. Veuillez réessayer.',
            'read_only_mode_enabled'  => 'Le mode lecture seule est activé. Vous ne pouvez pas vous inscrire pour le moment.',
        ],
    ],
    'register' => [
        'flash'                 => [
            'registered_successfully' => 'Inscription réussie. Profitez du site!',
        ],
        'legal_agreed_required' => 'Vous devez accepter nos conditions légales pour vous inscrire.',
        'legal_agreed_accepted' => 'Vous devez accepter nos conditions légales pour vous inscrire.',
    ],
    'release' => [
        'error' => [
            'unable_to_save_release' => 'Impossible de sauvegarder la version',
        ],
        'flash' => [
            'release_updated'  => 'Version mise à jour',
            'release_created'  => 'Version créée',
            'github_exception' => 'Une erreur s\'est produite lors de la communication avec Github: :message',
        ],
    ],
    'mappingversion' => [
        'created_successfully'      => 'Nouvelle version de cartographie ajoutée!',
        'created_bare_successfully' => 'Nouvelle version de cartographie ajoutée!',
        'deleted_successfully'      => 'Version de cartographie supprimée avec succès',
    ],
    'mdtimport' => [
        'unknown_dungeon' => 'Donjon inconnu',
        'error'           => [
            'mdt_string_parsing_failed'             => 'L\'analyse de la chaîne MDT a échoué. Avez-vous vraiment collé une chaîne MDT?',
            'mdt_string_format_not_recognized'      => 'Le format de la chaîne MDT n\'a pas été reconnu.',
            'cli_weakauras_parser_not_found'        => 'cli_weakauras_parser non installé.',
            'invalid_mdt_string_exception'          => 'Chaîne MDT invalide : %s',
            'invalid_mdt_string'                    => 'Chaîne MDT invalide',
            'mdt_importer_not_configured_properly'  => 'L\'importateur MDT n\'est pas configuré correctement. Veuillez contacter l\'administrateur à propos de ce problème.',
            'cannot_create_route_must_be_logged_in' => 'Vous devez être connecté pour créer un itinéraire',
        ],
    ],
    'path' => [
        'error' => [
            'unable_to_save_path'   => 'Impossible de sauvegarder le chemin',
            'unable_to_delete_path' => 'Impossible de supprimer le chemin',
        ],
    ],
    'patreon' => [
        'flash' => [
            'unlink_successful'       => 'Votre compte Patreon a été dissocié avec succès.',
            'link_successful'         => 'Votre compte Patreon a été lié avec succès. Merci!',
            'patreon_session_expired' => 'Votre session Patreon a expiré. Veuillez réessayer.',
            'session_expired'         => 'Votre session a expiré. Veuillez réessayer.',
            'patreon_error_occurred'  => 'Une erreur s\'est produite du côté de Patreon. Veuillez réessayer plus tard.',
            'internal_error_occurred' => 'Une erreur s\'est produite lors du traitement de la réponse de Patreon - elle semble être mal formée. L\'erreur a été enregistrée et sera traitée. Veuillez réessayer plus tard.',
        ],
    ],
    'profile' => [
        'flash' => [
            'email_already_in_use'             => 'Ce nom d\'utilisateur est déjà utilisé.',
            'username_already_in_use'         => 'Ce nom d\'utilisateur est déjà utilisé.',
            'profile_updated'                 => 'Profil mis à jour',
            'unexpected_error_when_saving'    => 'Une erreur inattendue s\'est produite lors de la tentative de sauvegarde de votre profil',
            'privacy_settings_updated'        => 'Paramètres de confidentialité mis à jour',
            'password_changed'                => 'Mot de passe modifié',
            'new_password_equals_old_password' => 'Le nouveau mot de passe est identique à l\'ancien mot de passe',
            'new_passwords_do_not_match'       => 'Les nouveaux mots de passe ne correspondent pas',
            'current_password_is_incorrect'   => 'Le mot de passe actuel est incorrect',
            'tag_created_successfully'        => 'Tag créé avec succès',
            'tag_already_exists'               => 'Ce tag existe déjà',
            'admins_cannot_delete_themselves' => 'Les administrateurs ne peuvent pas se supprimer!',
            'account_deleted_successfully'    => 'Compte supprimé avec succès.',
            'error_deleting_account'          => 'Une erreur s\'est produite. Veuillez réessayer.',
        ],
    ],
    'spell' => [
        'error' => [
            'unable_to_save_spell' => 'Impossible de sauvegarder le sort',
        ],
        'flash' => [
            'spell_updated' => 'Sort mis à jour',
            'spell_created' => 'Sort créé',
        ],
    ],
    'team' => [
        'flash' => [
            'team_updated'                        => 'Équipe mise à jour',
            'team_created'                        => 'Équipe créée',
            'unable_to_find_team_for_invite_code' => 'Impossible de trouver une équipe associée à ce code d\'invitation',
            'invite_accept_success'               => 'Succès! Vous êtes maintenant membre de l\'équipe %s.',
            'tag_created_successfully'            => 'Tag créé avec succès',
            'tag_already_exists'                  => 'Ce tag existe déjà',
        ],
    ],
    'user' => [
        'flash' => [
            'user_is_now_an_admin'       => 'L\'utilisateur :user est maintenant un administrateur',
            'user_is_no_longer_an_admin' => 'L\'utilisateur :user n\'est plus un administrateur',
            'user_is_now_a_user'         => 'L\'utilisateur :user est maintenant un utilisateur',
            'user_is_now_a_role'         => 'L\'utilisateur :user est maintenant un :role',
            'account_deleted_successfully'      => 'Compte supprimé avec succès.',
            'account_deletion_error'            => 'Une erreur s\'est produite. Veuillez réessayer.',
            'user_is_not_a_patron'       => 'Cet utilisateur n\'est pas un Patron.',
            'all_benefits_granted_successfully' => 'Tous les avantages ont été accordés avec succès.',
            'error_granting_all_benefits'       => 'Une erreur s\'est produite lors de la tentative d\'attribution de tous les avantages.',
        ],
    ],

];
