<?php

return [
    'error' => [
        'unauthenticated'                   => 'Non authentifié. Connectez-vous avec un jeton d\'accès OAuth.',
        'feature_inactive'                  => 'Le serveur MCP de keystone.guru n\'est pas disponible pour votre compte.',
        'missing_scope'                     => 'Il manque à votre jeton d\'accès l\'autorisation :scope requise par cet outil. Reconnectez-vous et accordez-la.',
        'rate_limited'                      => 'Trop de requêtes pour cet outil. Réessayez dans :seconds s.',
        'request_refused'                   => 'La requête a été refusée.',
        'dungeon_not_found'                 => 'Aucun donjon actif ne correspond à ":dungeon". Utilisez list_dungeons pour obtenir les clés valides.',
        'route_not_found'                   => 'Vous n\'avez aucun itinéraire avec la clé ":route". Utilisez list_my_routes pour obtenir vos itinéraires.',
        'pull_not_found'                    => 'L\'itinéraire n\'a pas de pull :pull.',
        'unable_to_save_route'              => 'L\'itinéraire n\'a pas pu être enregistré.',
        'unable_to_save_pull'               => 'Le pull n\'a pas pu être enregistré.',
        'unable_to_delete_pull'             => 'Le pull n\'a pas pu être supprimé.',
        'no_enemies_given'                  => 'Indiquez au moins un ID de pack ou un ID d\'ennemi.',
        'unknown_pack_ids'                  => 'Ces ID de pack ne font pas partie du mapping du donjon de l\'itinéraire : :ids.',
        'unknown_enemy_ids'                 => 'Ces ID d\'ennemi ne font pas partie du mapping du donjon de l\'itinéraire : :ids.',
        'enemies_already_pulled'            => 'Ces ennemis sont déjà dans un autre pull (ID d\'ennemi => numéro de pull) : :enemies. Retirez-les d\'abord de ce pull, ou laissez-les de côté.',
        'unknown_spell_ids'                 => 'Ces ID de sort ne peuvent pas être lancés dans un pull : :ids. Utilisez get_route_options pour obtenir les sorts qu\'un pull peut avoir.',
        'unknown_floor'                     => 'L\'étage :floor n\'est pas un étage du donjon de l\'itinéraire. Utilisez list_dungeons pour obtenir les étages.',
        'unknown_dungeon_difficulty'        => 'La difficulté :difficulty n\'est pas une difficulté de speedrun de ce donjon. Utilisez list_dungeons pour obtenir les difficultés de chaque donjon.',
        'unknown_dungeon_floor'             => 'L\'étage :floor n\'est pas un étage du donjon. Utilisez list_dungeons pour obtenir les étages.',
        'unknown_dungeon_start'             => 'Le début de donjon :start n\'appartient pas au mapping du donjon de l\'itinéraire. Utilisez get_route_options avec le donjon pour obtenir ses débuts de donjon.',
        'specialization_not_of_class'       => 'Le membre du groupe :member a une spécialisation d\'une autre classe.',
        'specialization_not_of_spell_class' => 'La spécialisation :specialization n\'est pas une spécialisation de la classe :class. Utilisez get_route_options pour obtenir les spécialisations de chaque classe.',
        'reorder_mismatch'                  => 'L\'ordre doit contenir chaque ID de pull de l\'itinéraire exactement une fois. ID de pull de l\'itinéraire : :ids.',
    ],
];
