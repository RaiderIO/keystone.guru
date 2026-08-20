<?php

return [
    'directory' => [
        'title'              => 'Créateurs de routes',
        'header'             => 'Créateurs de routes',
        'description'        => 'Parcourez les personnes qui créent des routes sur Keystone.guru. Les créateurs ayant des routes publiées sont listés automatiquement - ouvrez un profil pour voir leurs routes épinglées et où les retrouver ailleurs.',
        'search_label'       => 'Rechercher un créateur',
        'search_placeholder' => 'Rechercher par nom',
        'search_submit'      => 'Rechercher',
        'empty'              => 'Il n\'y a pas encore de créateurs listés.',
        'empty_for_search'   => 'Aucun créateur trouvé correspondant à « :search ».',
    ],
    'featured' => [
        'title'   => 'Créateurs en vedette',
        'see_all' => 'Voir tous les créateurs',
        /** Tooltip on a rail entry - it carries the name because the name itself may be clipped to an ellipsis */
        'entry_title' => ':name - :routes',
    ],
    'card' => [
        'route_count' => '{0} Aucune route publiée|{1} :count route publiée|[2,*] :count routes publiées',
    ],
];
