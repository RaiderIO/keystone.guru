<?php

return [
    'admintools' => [
        'error' => [
            'cli_weakauras_parser_not_found'      => 'cli_weakauras_parser non installé.',
            'invalid_mdt_string'                  => 'Chaîne MDT invalide',
            'invalid_mdt_string_exception'        => 'Chaîne MDT invalide: %s',
            'mdt_importer_not_configured'         => 'L\'importateur MDT n\'est pas configuré correctement. Veuillez contacter l\'administrateur à propos de ce problème.',
            'mdt_invalid_category'                => 'Catégorie invalide',
            'mdt_mismatched_enemy_count'          => 'Le PNJ %s a un nombre d\'ennemis non correspondant, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces'         => 'Le PNJ %s a des forces ennemies non correspondantes, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_forces_teeming' => 'Le PNJ %s a des forces ennemies grouillantes non correspondantes, MDT: %s, KG: %s',
            'mdt_mismatched_enemy_type'           => 'Le PNJ %s a un type d\'ennemi non correspondant, MDT: %s, KG: %s',
            'mdt_mismatched_health'               => 'Le PNJ %s a des valeurs de santé non correspondantes, MDT: %s, KG: %s',
            'mdt_string_format_not_recognized'    => 'Le format de la chaîne MDT n\'a pas été reconnu.',
            'mdt_string_parsing_failed'           => 'Échec de l\'analyse de la chaîne MDT. Avez-vous vraiment collé une chaîne MDT?',
            'mdt_unable_to_find_npc_for_id'       => 'Impossible de trouver le PNJ pour l\'identifiant %d',
        ],
        'flash' => [
            'caches_dropped_successfully'     => 'Caches supprimées avec succès',
            'exception'                       => 'Exception lancée dans le panneau d\'administration',
            'feature_forgotten'               => 'Fonctionnalité :feature oubliée avec succès',
            'feature_toggle_activated'        => 'La fonctionnalité :feature est maintenant activée',
            'feature_toggle_deactivated'      => 'La fonctionnalité :feature est maintenant désactivée',
            'message_banner_set_successfully' => 'Bannière de message définie avec succès',
            'read_only_mode_disabled'         => 'Mode lecture seule désactivé',
            'read_only_mode_enabled'          => 'Mode lecture seule activé',
            'releases_exported'               => 'Versions exportées',
            'thumbnail_regenerate_result'     => ':success tâches envoyées pour :total itinéraires. :failed a échoué.',
        ],
    ],
    'apidungeonroute' => [
        'mdt_generate_error'  => 'Une erreur s\'est produite lors de la génération de votre chaîne MDT: %s',
        'mdt_generate_no_lua' => 'L\'importateur MDT n\'est pas configuré correctement. Veuillez contacter l\'administrateur à propos de ce problème',
    ],
    'apiuserreport' => [
        'error' => [
            'unable_to_save_report'        => 'Impossible de sauvegarder le rapport',
            'unable_to_update_user_report' => 'Impossible de mettre à jour le rapport utilisateur',
        ],
    ],
    'brushline' => [
        'error' => [
            'unable_to_delete_brushline' => 'Impossible de supprimer la ligne',
            'unable_to_save_brushline'   => 'Impossible de sauvegarder la ligne',
        ],
    ],
    'dungeon' => [
        'flash' => [
            'dungeon_created' => 'Donjon créé',
            'dungeon_updated' => 'Donjon mis à jour',
        ],
    ],
    'dungeonroute' => [
        'flash' => [
            'route_cloned_successfully' => 'Itinéraire cloné avec succès',
            'route_created'             => 'Itinéraire créé',
            'route_updated'             => 'Itinéraire mis à jour',
        ],
        'unable_to_save' => 'Impossible de sauvegarder l\'itinéraire',
    ],
    'dungeonroutediscover' => [
        'dungeon' => [
            'new'               => '%s nouveaux itinéraires',
            'next_week_affixes' => '%s la semaine prochaine',
            'popular'           => '%s itinéraires populaires',
            'this_week_affixes' => '%s cette semaine',
        ],
        'new'               => 'Nouveau',
        'next_week_affixes' => 'Les affixes de la semaine prochaine',
        'popular'           => 'Itinéraires populaires',
        'season'            => [
            'new'               => '%s nouveaux itinéraires',
            'next_week_affixes' => '%s la semaine prochaine',
            'popular'           => '%s itinéraires populaires',
            'this_week_affixes' => '%s cette semaine',
        ],
        'this_week_affixes' => 'Les affixes de cette semaine',
    ],
    'dungeonspeedrunrequirednpcs' => [
        'flash' => [
            'npc_added_successfully'   => 'PNJ ajouté avec succès',
            'npc_deleted_successfully' => 'PNJ supprimé avec succès',
        ],
        'no_linked_npc' => 'Aucun PNJ lié',
    ],
    'expansion' => [
        'flash' => [
            'expansion_created'        => 'Extension créée',
            'expansion_updated'        => 'Extension mise à jour',
            'unable_to_save_expansion' => 'Impossible de sauvegarder l\'extension',
        ],
    ],
    'generic' => [
        'error' => [
            'floor_not_found_in_dungeon' => 'Étage non inclus dans le donjon',
            'not_found'                  => 'Non trouvé',
        ],
    ],
    'mappingversion' => [
        'created_bare_successfully' => 'Nouvelle version de cartographie ajoutée!',
        'created_successfully'      => 'Nouvelle version de cartographie ajoutée!',
        'deleted_successfully'      => 'Version de cartographie supprimée avec succès',
    ],
    'mdtimport' => [
        'error' => [
            'cannot_create_route_must_be_logged_in' => 'Vous devez être connecté pour créer un itinéraire',
            'cli_weakauras_parser_not_found'        => 'cli_weakauras_parser non installé.',
            'invalid_mdt_string'                    => 'Chaîne MDT invalide',
            'invalid_mdt_string_exception'          => 'Chaîne MDT invalide : %s',
            'mdt_importer_not_configured_properly'  => 'L\'importateur MDT n\'est pas configuré correctement. Veuillez contacter l\'administrateur à propos de ce problème.',
            'mdt_string_format_not_recognized'      => 'Le format de la chaîne MDT n\'a pas été reconnu.',
            'mdt_string_parsing_failed'             => 'L\'analyse de la chaîne MDT a échoué. Avez-vous vraiment collé une chaîne MDT?',
        ],
        'unknown_dungeon' => 'Donjon inconnu',
    ],
    'oauthlogin' => [
        'flash' => [
            'email_exists'            => 'Il y a déjà un utilisateur avec l\'adresse e-mail %s. Vous êtes-vous déjà inscrit auparavant?',
            'permission_denied'       => 'Impossible de s\'inscrire - la demande a été refusée. Veuillez réessayer.',
            'read_only_mode_enabled'  => 'Le mode lecture seule est activé. Vous ne pouvez pas vous inscrire pour le moment.',
            'registered_successfully' => 'Inscription réussie. Profitez du site!',
            'user_exists'             => 'Il y a déjà un utilisateur avec le nom d\'utilisateur %s. Vous êtes-vous déjà inscrit auparavant?',
        ],
    ],
    'path' => [
        'error' => [
            'unable_to_delete_path' => 'Impossible de supprimer le chemin',
            'unable_to_save_path'   => 'Impossible de sauvegarder le chemin',
        ],
    ],
    'patreon' => [
        'flash' => [
            'internal_error_occurred' => 'Une erreur s\'est produite lors du traitement de la réponse de Patreon - elle semble être mal formée. L\'erreur a été enregistrée et sera traitée. Veuillez réessayer plus tard.',
            'link_successful'         => 'Votre compte Patreon a été lié avec succès. Merci!',
            'patreon_error_occurred'  => 'Une erreur s\'est produite du côté de Patreon. Veuillez réessayer plus tard.',
            'patreon_session_expired' => 'Votre session Patreon a expiré. Veuillez réessayer.',
            'session_expired'         => 'Votre session a expiré. Veuillez réessayer.',
            'unlink_successful'       => 'Votre compte Patreon a été dissocié avec succès.',
        ],
    ],
    'profile' => [
        'flash' => [
            'account_deleted_successfully'     => 'Compte supprimé avec succès.',
            'admins_cannot_delete_themselves'  => 'Les administrateurs ne peuvent pas se supprimer!',
            'current_password_is_incorrect'    => 'Le mot de passe actuel est incorrect',
            'email_already_in_use'             => 'Ce nom d\'utilisateur est déjà utilisé.',
            'error_deleting_account'           => 'Une erreur s\'est produite. Veuillez réessayer.',
            'new_password_equals_old_password' => 'Le nouveau mot de passe est identique à l\'ancien mot de passe',
            'new_passwords_do_not_match'       => 'Les nouveaux mots de passe ne correspondent pas',
            'password_changed'                 => 'Mot de passe modifié',
            'privacy_settings_updated'         => 'Paramètres de confidentialité mis à jour',
            'profile_updated'                  => 'Profil mis à jour',
            'tag_already_exists'               => 'Ce tag existe déjà',
            'tag_created_successfully'         => 'Tag créé avec succès',
            'unexpected_error_when_saving'     => 'Une erreur inattendue s\'est produite lors de la tentative de sauvegarde de votre profil',
            'username_already_in_use'          => 'Ce nom d\'utilisateur est déjà utilisé.',
        ],
    ],
    'register' => [
        'flash' => [
            'registered_successfully' => 'Inscription réussie. Profitez du site!',
        ],
        'legal_agreed_accepted' => 'Vous devez accepter nos conditions légales pour vous inscrire.',
        'legal_agreed_required' => 'Vous devez accepter nos conditions légales pour vous inscrire.',
    ],
    'release' => [
        'error' => [
            'unable_to_save_release' => 'Impossible de sauvegarder la version',
        ],
        'flash' => [
            'github_exception' => 'Une erreur s\'est produite lors de la communication avec Github: :message',
            'release_created'  => 'Version créée',
            'release_updated'  => 'Version mise à jour',
        ],
    ],
    'spell' => [
        'error' => [
            'unable_to_save_spell' => 'Impossible de sauvegarder le sort',
        ],
        'flash' => [
            'spell_created' => 'Sort créé',
            'spell_updated' => 'Sort mis à jour',
        ],
    ],
    'team' => [
        'flash' => [
            'invite_accept_success'               => 'Succès! Vous êtes maintenant membre de l\'équipe %s.',
            'tag_already_exists'                  => 'Ce tag existe déjà',
            'tag_created_successfully'            => 'Tag créé avec succès',
            'team_created'                        => 'Équipe créée',
            'team_updated'                        => 'Équipe mise à jour',
            'unable_to_find_team_for_invite_code' => 'Impossible de trouver une équipe associée à ce code d\'invitation',
        ],
    ],
    'user' => [
        'flash' => [
            'account_deleted_successfully'      => 'Compte supprimé avec succès.',
            'account_deletion_error'            => 'Une erreur s\'est produite. Veuillez réessayer.',
            'all_benefits_granted_successfully' => 'Tous les avantages ont été accordés avec succès.',
            'error_granting_all_benefits'       => 'Une erreur s\'est produite lors de la tentative d\'attribution de tous les avantages.',
            'user_is_no_longer_an_admin'        => 'L\'utilisateur :user n\'est plus un administrateur',
            'user_is_not_a_patron'              => 'Cet utilisateur n\'est pas un Patron.',
            'user_is_now_a_role'                => 'L\'utilisateur :user est maintenant un :role',
            'user_is_now_a_user'                => 'L\'utilisateur :user est maintenant un utilisateur',
            'user_is_now_an_admin'              => 'L\'utilisateur :user est maintenant un administrateur',
        ],
    ],
];
