<?php

return [
    'directory' => [
        'title'                   => 'Criadores de rotas',
        'header'                  => 'Criadores de rotas',
        'description'             => 'Navegue pelas pessoas que criam rotas no Keystone.guru. Criadores cujo número de rotas publicadas é de pelo menos :min são listados automaticamente - abra um perfil para ver suas rotas fixadas e onde mais encontrá-los.',
        'search_label'            => 'Buscar um criador',
        'search_placeholder'      => 'Buscar por nome',
        'search_submit'           => 'Buscar',
        'category_label'          => 'Cria rotas para',
        'category_any'            => 'Todos',
        'category_option'         => 'Jogadores de nível :category',
        'sort_label'              => 'Ordenar por',
        'sort_active_this_season' => 'Ativos nesta temporada',
        'sort_most_routes'        => 'Mais rotas',
        'empty'                   => 'Ainda não há criadores listados.',
        'empty_for_category'      => 'Ainda não há criadores compartilhando uma coleção ":category".',
        'empty_for_search'        => 'Nenhum criador encontrado correspondendo a ":search".',
        'empty_for_dungeon'       => '',
        'filtered_to_dungeon'     => '',
        'clear_dungeon_filter'    => '',
    ],
    'featured' => [
        'title'           => 'Criadores em destaque',
        'title_dungeon'   => 'Criadores para :dungeon',
        'see_all_dungeon' => '',
        /** Tooltip on a rail entry - it carries the name because a name past two lines is clipped to an ellipsis */
        'entry_title'         => ':name - :routes',
        'route_count'         => '{1} :count rota|[2,*] :count rotas',
        'dungeon_route_count' => '{1} :count rota para :dungeon|[2,*] :count rotas para :dungeon',
    ],
    'stats' => [
        'route_count'              => '{0} Nenhuma rota|{1} :count rota|[2,*] :count rotas',
        'route_count_total'        => '{0} Nenhuma rota publicada|{1} :count rota no total|[2,*] :count rotas no total',
        'season_route_count'       => '{1} :count rota nesta temporada|[2,*] :count rotas nesta temporada',
        'season_route_count_named' => '{0} Nenhuma rota na :season|{1} :count rota na :season|[2,*] :count rotas na :season',
        'no_routes_this_season'    => 'Nenhuma rota nesta temporada',
        'views'                    => '{0} Nenhuma visualização|{1} :views visualização|[2,*] :views visualizações',
        'rating'                   => '{1} ★ :rating (:count avaliação)|[2,*] ★ :rating (:count avaliações)',
        'last_published'           => 'Última publicação: :time',
    ],
];
