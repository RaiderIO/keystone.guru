<?php

return [
    'directory' => [
        'title'              => 'Creadores de rutas',
        'header'             => 'Creadores de rutas',
        'description'        => 'Explora a las personas que crean rutas en Keystone.guru. Los creadores con rutas publicadas aparecen automáticamente en la lista - abre un perfil para ver sus rutas fijadas y dónde más encontrarlos.',
        'search_label'       => 'Buscar un creador',
        'search_placeholder' => 'Buscar por nombre',
        'search_submit'      => 'Buscar',
        'empty'              => 'Todavía no hay creadores en la lista.',
        'empty_for_search'   => 'No se encontraron creadores que coincidan con ":search".',
    ],
    'featured' => [
        'title'   => 'Creadores destacados',
        'see_all' => 'Ver todos los creadores',
        /** Tooltip on a rail entry - it carries the name because the name itself may be clipped to an ellipsis */
        'entry_title' => ':name - :routes',
    ],
    'card' => [
        'route_count' => '{0} Sin rutas publicadas|{1} :count ruta publicada|[2,*] :count rutas publicadas',
    ],
];
