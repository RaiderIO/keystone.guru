<?php

return [
    'directory' => [
        'title'                   => 'Creadores de rutas',
        'header'                  => 'Creadores de rutas',
        'description'             => 'Explora a las personas que crean rutas en Keystone.guru. Los creadores con rutas publicadas (al menos :min) se listan automáticamente; abre un perfil para ver sus rutas fijadas y dónde más encontrarlos.',
        'search_label'            => 'Buscar un creador',
        'search_placeholder'      => 'Buscar por nombre',
        'search_submit'           => 'Buscar',
        'category_label'          => 'Crea rutas para',
        'category_any'            => 'Todos',
        'category_option'         => 'Jugadores (:category)',
        'sort_label'              => 'Ordenar por',
        'sort_active_this_season' => 'Activos esta temporada',
        'sort_most_routes'        => 'Más rutas',
        'empty'                   => 'Todavía no hay creadores listados.',
        'empty_for_category'      => 'Todavía no hay creadores que compartan una colección ":category".',
        'empty_for_search'        => 'No se encontraron creadores que coincidan con ":search".',
        'empty_for_dungeon'       => '',
        'filtered_to_dungeon'     => '',
        'clear_dungeon_filter'    => '',
    ],
    'featured' => [
        'title'           => 'Creadores destacados',
        'title_dungeon'   => 'Creadores para :dungeon',
        'see_all_dungeon' => '',
        /** Tooltip on a rail entry - it carries the name because a name past two lines is clipped to an ellipsis */
        'entry_title'         => ':name - :routes',
        'route_count'         => '{1} :count ruta|[2,*] :count rutas',
        'dungeon_route_count' => '{1} :count ruta de :dungeon|[2,*] :count rutas de :dungeon',
    ],
    'stats' => [
        'route_count'              => '{0} Sin rutas|{1} :count ruta|[2,*] :count rutas',
        'route_count_total'        => '{0} Sin rutas publicadas|{1} :count ruta en total|[2,*] :count rutas en total',
        'season_route_count'       => '{1} :count ruta esta temporada|[2,*] :count rutas esta temporada',
        'season_route_count_named' => '{0} Sin rutas en :season|{1} :count ruta en :season|[2,*] :count rutas en :season',
        'no_routes_this_season'    => 'Sin rutas esta temporada',
        'views'                    => '{0} Sin vistas|{1} :views vista|[2,*] :views vistas',
        'rating'                   => '{1} ★ :rating (:count calificación)|[2,*] ★ :rating (:count calificaciones)',
        'last_published'           => 'Última publicación: :time',
    ],
];
