<?php

return [
    'index' => [
        'title'       => 'Compendium',
        'header'      => 'Compendium',
        'intro'       => 'Le Compendium est une encyclopédie communautaire de tous les donjons de la saison actuelle du jeu. Découvrez exactement ce que fait chaque PNJ, quels sorts il lance, comment le contrer, et quel contrôle de foule fonctionne sur lui.',
        'data_source' => [
            'title'       => 'Toujours à jour',
            'description' => 'Le Compendium est maintenu en temps réel grâce aux journaux de combat que les joueurs téléchargent automatiquement via le client Raider.IO. Chaque run suivi améliore discrètement les données pour tout le monde.',
            'cta'         => 'Installer le client Raider.IO',
        ],
        'how_it_works' => [
            'title'  => 'Comment ça marche',
            'step_1' => [
                'title'       => 'Choisissez une section',
                'description' => 'Parcourez les PNJ, les sorts, l\'activité récente, ou accédez directement au contrôle de foule par classe.',
            ],
            'step_2' => [
                'title'       => 'Rechercher et filtrer',
                'description' => 'Filtrez par donjon pour vous concentrer exactement sur les pulls que vous préparez.',
            ],
            'step_3' => [
                'title'       => 'Approfondissez les détails',
                'description' => 'Ouvrez un PNJ ou un sort pour voir les écoles, types de dissipation, mécaniques, durées et plus encore.',
            ],
        ],
        'cards' => [
            'npc' => [
                'title'        => 'PNJ',
                'description'  => 'Chaque PNJ du jeu avec ses capacités, sa santé, sa classification et les donjons dans lesquels il apparaît.',
                'cta'          => 'Parcourir les PNJ',
                'count_suffix' => 'PNJ répertoriés',
            ],
            'spell' => [
                'title'        => 'Sorts',
                'description'  => 'Recherchez n\'importe quel sort pour voir ce qu\'il fait, comment l\'éviter, et quels PNJ le lancent.',
                'cta'          => 'Parcourir les sorts',
                'count_suffix' => 'sorts répertoriés',
            ],
            'activity' => [
                'title'       => 'Activité',
                'description' => 'Un flux en direct des nouvelles données provenant de la communauté, organisées par jour.',
                'cta'         => 'Voir l\'activité',
                'subtitle'    => 'Mis à jour quotidiennement',
            ],
            'class' => [
                'title'        => 'Par classe',
                'description'  => 'Découvrez lesquels de vos sorts de contrôle de foule fonctionnent sur quels PNJ, regroupés par classe.',
                'cta'          => 'Parcourir par classe',
                'count_suffix' => 'classes couvertes',
            ],
        ],
    ],
    'event' => [
        'characteristic_added'    => 'Affecté par :name',
        'characteristic_removed'  => 'Non affecté par :name',
        'spell_assigned'          => 'Lance :name',
        'spell_created'           => ':spell ajouté à la base de données',
        'property_changed'        => 'Affecté par :property',
        'property_removed'        => 'Non affecté par :property',
        'counter_added'           => ':spell peut désormais être contré par :property',
        'counter_removed'         => ':spell ne peut plus être contré par :property',
        'school_recorded'         => ':spell inflige des dégâts de :schools',
        'immunity_bypass_added'   => ':spell a été observé touchant à travers :property',
        'immunity_bypass_removed' => ':spell n\'a plus été observé touchant à travers :property',
        // Subject-less variants: used when the row already leads with the spell link as its
        // subject, so the description does not repeat the spell name
        'spell_created_no_subject'           => 'Ajouté à la base de données',
        'counter_added_no_subject'           => 'Peut désormais être contré par :property',
        'counter_removed_no_subject'         => 'Ne peut plus être contré par :property',
        'school_recorded_no_subject'         => 'Inflige des dégâts de :schools',
        'immunity_bypass_added_no_subject'   => 'Observé touchant à travers :property',
        'immunity_bypass_removed_no_subject' => 'N\'a plus été observé touchant à travers :property',
        'count'                              => ':count événement|:count événements',
        'more'                               => 'et :count de plus',
        'property'                           => [
            'aura'   => 'Aura',
            'debuff' => 'Affaiblissement',
        ],
    ],
    'npc' => [
        'index' => [
            'title'                 => 'Compendium des PNJ',
            'header'                => 'Compendium des PNJ',
            'boss'                  => 'Boss',
            'table_header_name'     => 'Nom',
            'table_header_dungeons' => 'Donjons',
            'table_header_spells'   => 'Sorts',
        ],
        'show' => [
            'title'   => ':name - Compendium des PNJ',
            'wowhead' => 'Voir sur Wowhead',
        ],
        'sections' => [
            'header' => [
                'level' => 'Niveau',
            ],
            'characteristics' => [
                'title'        => 'Caractéristiques',
                'tooltip'      => 'Par quoi ce PNJ est-il affecté ?',
                'empty'        => 'Aucune caractéristique enregistrée.',
                'not_observed' => 'Non observé :',
            ],
            'spells' => [
                'title'                              => 'Sorts',
                'empty'                              => 'Aucun sort enregistré.',
                'header_name'                        => 'Nom',
                'header_schools'                     => 'Écoles',
                'header_schools_tooltip'             => 'Quel type de dégâts ce sort inflige-t-il ?',
                'header_miss_types'                  => 'Types d\'échec',
                'header_miss_types_tooltip'          => 'Que pouvez-vous faire pour éviter ce sort ?',
                'header_counters'                    => 'Contres',
                'header_counters_tooltip'            => 'Capacités des joueurs pouvant faire échouer ce sort ou lui faire changer de cible.',
                'header_bypasses_immunities'         => 'Contourne l\'immunité',
                'header_bypasses_immunities_tooltip' => 'Immunités de joueur qui n\'arrêtent pas ce sort - il a été observé touchant alors qu\'elles étaient actives.',
                'header_dispel_type'                 => 'Type de dissipation',
                'header_dispel_type_tooltip'         => 'Quel type de dissipation peut être utilisé pour retirer ce sort ?',
                'header_mechanic'                    => 'Mécanique',
                'header_cast_time'                   => 'Temps d\'incantation',
                'header_duration'                    => 'Durée',
            ],
            'event_feed' => [
                'title' => 'Activité récente',
                'empty' => 'Aucune activité enregistrée pour le moment.',
            ],
        ],
    ],
    'spell' => [
        'index' => [
            'title'                 => 'Compendium des sorts',
            'header'                => 'Compendium des sorts',
            'table_header_name'     => 'Nom',
            'table_header_dungeons' => 'Donjons',
            'table_header_used_by'  => 'Utilisé par',
        ],
        'show' => [
            'title'   => ':name - Compendium des sorts',
            'wowhead' => 'Voir sur Wowhead',
        ],
        'sections' => [
            'header' => [
                'aura'   => 'Aura',
                'debuff' => 'Affaiblissement',
            ],
            'description' => [
                'title' => 'Description',
            ],
            'details' => [
                'title'                              => 'Détails',
                'header_schools'                     => 'Écoles',
                'header_schools_tooltip'             => 'Quel type de dégâts ce sort inflige-t-il ?',
                'header_miss_types'                  => 'Types d\'échec',
                'header_miss_types_tooltip'          => 'Que pouvez-vous faire pour éviter ce sort ?',
                'header_counters'                    => 'Contres',
                'header_counters_tooltip'            => 'Capacités des joueurs pouvant faire échouer ce sort ou lui faire changer de cible.',
                'header_bypasses_immunities'         => 'Contourne l\'immunité',
                'header_bypasses_immunities_tooltip' => 'Immunités de joueur qui n\'arrêtent pas ce sort - il a été observé touchant alors qu\'elles étaient actives.',
                'header_dispel_type'                 => 'Type de dissipation',
                'header_dispel_type_tooltip'         => 'Quel type de dissipation peut être utilisé pour retirer ce sort ?',
                'header_mechanic'                    => 'Mécanique',
                'header_cast_time'                   => 'Temps d\'incantation',
                'header_duration'                    => 'Durée',
            ],
            'dungeons' => [
                'title'       => 'Donjons',
                'empty'       => 'Non lié à aucun donjon.',
                'header_name' => 'Nom',
            ],
            'npcs' => [
                'title'                 => 'Utilisé par',
                'empty'                 => 'Aucun PNJ enregistré.',
                'header_name'           => 'Nom',
                'header_classification' => 'Classification',
                'header_dungeons'       => 'Donjons',
            ],
            'event_feed' => [
                'title' => 'Activité récente',
                'empty' => 'Aucune activité enregistrée pour le moment.',
            ],
        ],
    ],
    'activity' => [
        'index' => [
            'title'  => 'Activité du compendium',
            'header' => 'Activité du compendium',
            'empty'  => 'Aucune activité enregistrée pour le moment.',
        ],
        'day' => [
            'title'  => ':date - Activité du compendium',
            'header' => 'Activité du compendium pour le :date',
            'empty'  => 'Aucune activité enregistrée pour ce jour.',
        ],
    ],
    'class' => [
        'index' => [
            'title'  => 'Compendium - Par classe',
            'header' => 'Par classe',
        ],
        'show' => [
            'title'                       => ':name - Par classe',
            'table_header_spell'          => 'Sort',
            'table_header_characteristic' => 'Caractéristique',
            'table_header_npcs'           => 'PNJ notables',
            'no_spells'                   => 'Aucun sort de contrôle de foule trouvé pour cette classe dans cette version du jeu.',
            'no_npcs'                     => '-',
            'npcs_no_effect'              => 'Immunisé',
            'npcs_works_on'               => 'Fonctionne sur',
            'npcs_no_exceptions'          => 'Rien d\'inattendu',
            'npcs_no_data'                => 'Aucune donnée',
            'npcs_description'            => 'Seules les surprises sont listées - le trash qui a résisté, et les boss sur lesquels ça a quand même fonctionné. Tout ce qui se comporte comme vous vous y attendez déjà est omis. « Aucun effet observé » signifie qu\'un autre contrôle de foule de ce tableau a été vu toucher ce PNJ mais que celui-ci n\'a jamais touché : c\'est une preuve, pas une immunité confirmée.',
            'counters'                    => [
                'title'              => 'Capacités contrables',
                'racial'             => 'Raciale (:race)',
                'no_spells'          => 'Aucun sort de PNJ contrable trouvé pour ce donjon.',
                'table_header_spell' => 'Sort',
                'table_header_npcs'  => 'PNJ',
            ],
            'reflect' => [
                'title'              => 'Sorts réfléchissables',
                'description'        => 'Sorts de PNJ dans ce donjon qui ont été observés être réfléchis.',
                'no_spells'          => 'Aucun sort de PNJ réfléchissable trouvé pour ce donjon.',
                'table_header_spell' => 'Sort',
                'table_header_npcs'  => 'PNJ',
            ],
        ],
    ],
];
