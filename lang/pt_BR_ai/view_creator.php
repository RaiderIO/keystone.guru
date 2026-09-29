<?php

return [
    'directory' => [
        'title'                   => 'Criadores de rotas',
        'header'                  => 'Criadores de rotas',
        'description'             => 'Navegue pelas pessoas que criam rotas no Keystone.guru. Criadores com rotas publicadas são listados automaticamente - abra um perfil para ver suas rotas fixadas e onde mais encontrá-los.',
        'search_label'            => 'Buscar um criador',
        'search_placeholder'      => 'Buscar por nome',
        'search_submit'           => 'Buscar',
        'category_label'          => 'Filtrar pelo tipo de coleções que um criador compartilha',
        'category_any'            => 'Qualquer coleção',
        'category_option'         => '',
        'sort_label'              => '',
        'sort_active_this_season' => '',
        'sort_most_routes'        => '',
        'empty'                   => 'Ainda não há criadores listados.',
        'empty_for_category'      => 'Ainda não há criadores compartilhando uma coleção ":category".',
        'empty_for_search'        => 'Nenhum criador encontrado correspondendo a ":search".',
    ],
    'featured' => [
        'title'         => 'Criadores em destaque',
        'title_dungeon' => '',
        'see_all'       => 'Ver todos os criadores',
        /** Tooltip on a rail entry - it carries the name because the name itself may be clipped to an ellipsis */
        'entry_title'         => ':name - :routes',
        'route_count'         => '',
        'dungeon_route_count' => '',
    ],
    'stats' => [
        'route_count'              => '',
        'route_count_total'        => '',
        'season_route_count'       => '',
        'season_route_count_named' => '',
        'no_routes_this_season'    => '',
        'views'                    => '',
        'rating'                   => '',
        'last_published'           => '',
    ],
];
