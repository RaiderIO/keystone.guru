<?php

return [
    'directory' => [
        'title'              => 'Routen-Creator',
        'header'             => 'Routen-Creator',
        'description'        => 'Entdecke die Leute, die auf Keystone.guru Routen erstellen. Creator mit veröffentlichten Routen werden automatisch aufgeführt - öffne ein Profil, um die angehefteten Routen zu sehen und wo du sie sonst noch findest.',
        'search_label'       => 'Nach einem Creator suchen',
        'search_placeholder' => 'Nach Name suchen',
        'search_submit'      => 'Suchen',
        'empty'              => 'Es sind noch keine Creator aufgeführt.',
        'empty_for_search'   => 'Keine Creator gefunden, die zu ":search" passen.',
    ],
    'featured' => [
        'title'   => 'Empfohlene Creator',
        'see_all' => 'Alle Creator anzeigen',
        /** Tooltip on a rail entry - it carries the name because the name itself may be clipped to an ellipsis */
        'entry_title' => ':name - :routes',
    ],
    'card' => [
        'route_count' => '{0} Keine veröffentlichten Routen|{1} :count veröffentlichte Route|[2,*] :count veröffentlichte Routen',
    ],
];
